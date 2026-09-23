<?php

namespace App\Http\Controllers;

use App\Helpers\UpdateHelper;
use App\Models\Category;
use App\Models\Logs;
use App\Models\Project;
use App\Models\User;
use App\Models\Templates;
use App\Services\ProjectAccess;
use Illuminate\Validation\ValidationException;
use App\Repositories\AttachRepository;
use App\Repositories\ProcessRepository;
use App\Repositories\ReadySentRepository;
use App\Repositories\ScheduleRepository;
use App\Services\SendMailService;
use App\Services\UpdateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cookie;

class AjaxController extends Controller
{
    private const ADMIN_ONLY_ACTIONS = [
        'start_update',
    ];

    /**
     * Inject services and repositories used by shared admin AJAX actions.
     */
    public function __construct(
        private readonly UpdateService       $updateService,
        private readonly ScheduleRepository  $scheduleRepository,
        private readonly AttachRepository    $attachRepository,
        private readonly SendMailService     $sendMailService,
        private readonly ReadySentRepository $readySentRepository,
        private readonly ProcessRepository   $processRepository,
    )
    {
    }

    /**
     * Dispatch an AJAX action and return a normalized JSON response.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function action(Request $request): JsonResponse
    {
        @set_time_limit(0);

        $action = (string) $request->input('action');
        if (!in_array($action, ['get_categories', 'count_send', 'log_online', 'alert_update'], true)) {
            abort_unless($request->isMethod('POST'), 405);
        }
        if ($action !== 'change_lng') {
            abort_unless(Auth::check(), 401);
        }
        if (in_array($action, ['start_update', 'alert_update'], true)) {
            abort_unless($this->currentUserIsAdmin(), 403);
        }
        if (in_array($action, ['remove_schedule', 'remove_attach', 'send_test_email', 'send_out', 'count_send', 'log_online', 'start_mailing', 'process'], true)) {
            abort_unless(Auth::user()?->canManageProjects(), 403);
        }

        try {
            return response()->json($this->getResult($request));
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface|ValidationException|\Illuminate\Database\Eloquent\ModelNotFoundException|\Illuminate\Auth\Access\AuthorizationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'result' => false,
                'errors' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Resolve the requested AJAX action to the matching service or repository operation.
     *
     * @param Request $request
     * @return array|false[]|true[]
     * @throws \PHPMailer\PHPMailer\Exception
     * @throws \Throwable
     */
    private function getResult(Request $request): array
    {
        $action = (string)$request->input('action');

        if ($action === '') {
            return [];
        }

        if ($this->isAdminOnlyAction($action) && !$this->currentUserIsAdmin()) {
            return [
                'result' => false,
                'status' => __('frontend.msg.failed_to_update'),
            ];
        }

        $update = new UpdateHelper(app()->getLocale(), config('app.version'));

        return match ($action) {
            'start_update' => $this->updateService->startUpdate($update, $request),

            'alert_update' => $this->updateService->alertUpdate($update),

            'remove_schedule' => [
                'result' => $this->scheduleRepository->removeSchedule((int)$request->input('id')),
                'id' => (int)$request->input('id'),
            ],

            'change_lng' => $this->changeLanguage($request),

            'remove_attach' => $this->removeAttach($request),

            'send_test_email' => $this->sendMailService->sendTest($request),

            'send_out' => $this->sendMailService->sendOut($request),

            'count_send' => $this->sendMailService->countSend($request),

            'log_online' => $this->readySentRepository->logOnline(5, (int) $request->input('logId')),

            'start_mailing' => $this->startMailing($request),

            'get_categories' => [
                'items' => Category::query()->orderBy('name')->get(),
            ],

            'process' => $this->processCommand($request),

            default => [],
        };
    }

    /**
     * Determine whether the AJAX action can only be executed by administrators.
     *
     * @param string $action
     * @return bool
     */
    private function isAdminOnlyAction(string $action): bool
    {
        return in_array($action, self::ADMIN_ONLY_ACTIONS, true);
    }

    /**
     * Check whether the current session belongs to an administrator.
     *
     * @return bool
     */
    private function currentUserIsAdmin(): bool
    {
        return Auth::check() && Auth::user()?->role === User::ROLE_ADMIN;
    }

    /**
     * Store the selected interface locale in a long-lived cookie.
     *
     * @param Request $request
     * @return true[]
     */
    private function changeLanguage(Request $request): array
    {
        $locale = (string)$request->input('locale');

        if ($locale !== '' && in_array($locale, Config::get('app.locales', []), true)) {
            Cookie::queue(
                Cookie::forever('lang', $locale)
            );
        }

        return ['result' => true];
    }

    /**
     * Remove an uploaded template attachment by ID.
     *
     * @param Request $request
     * @return true[]
     */
    private function removeAttach(Request $request): array
    {
        $this->attachRepository->remove((int)$request->input('id'));

        return ['result' => true];
    }

    /**
     * Create a log record that marks the start of a manual mailing run.
     *
     * @return array
     */
    private function startMailing(Request $request): array
    {
        $data = $request->validate([
            'templateId' => ['required', 'array', 'min:1'],
            'templateId.*' => ['required', 'integer', 'distinct'],
            'categoryId' => ['nullable', 'array'],
            'categoryId.*' => ['integer', 'distinct'],
        ]);
        $templates = ProjectAccess::scope(Templates::query(), 'manage')
            ->whereHas('project', fn ($query) => $query->includingDefault()->where('status', true))
            ->whereIn('id', $data['templateId'])->get();
        abort_unless($templates->count() === count($data['templateId']), 403);

        if ($templates->contains(fn ($template) => (int) $template->project_id === Project::DEFAULT_ID)) {
            $request->validate(['categoryId' => ['required', 'array', 'min:1']]);
        }

        $categories = $data['categoryId'] ?? [];
        abort_unless(Category::query()
            ->whereIn('id', $categories)->count() === count($categories), 403);

        $log = Logs::query()->create([
            'time' => now(),
            'user_id' => Auth::id(),
        ]);

        return [
            'result' => true,
            'logId' => $log->id,
        ];
    }

    /**
     * Persist the current user's long-running process command.
     *
     * @param Request $request
     * @return array|false[]
     */
    private function processCommand(Request $request): array
    {
        $command = $request->input('command');

        if (empty($command)) {
            return ['result' => false];
        }

        $userId = Auth::id();

        if (!$userId) {
            return ['result' => false];
        }

        $this->processRepository->updateByUserId($userId, $command);

        return [
            'result' => true,
            'command' => $command,
        ];
    }
}
