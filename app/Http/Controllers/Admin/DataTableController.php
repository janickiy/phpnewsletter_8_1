<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Helpers\StringHelper;
use App\Models\Category;
use App\Models\Logs;
use App\Models\Macros;
use App\Models\Project;
use App\Models\ReadySent;
use App\Models\Redirect;
use App\Models\Smtp;
use App\Models\Subscribers;
use App\Models\Templates;
use App\Models\User;
use App\Repositories\ProjectRepository;
use App\Services\ProjectAccess;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\JsonResponse;
use Yajra\DataTables\Facades\DataTables;

class DataTableController extends Controller
{
    public function getProjects(ProjectRepository $projectRepository): JsonResponse
    {
        $rows = $projectRepository->getForDataTable();

        return DataTables::of($rows)
            ->editColumn('status', fn ($row) => $row->status ? __('frontend.str.projects.active') : __('frontend.str.projects.inactive'))
            ->addColumn('actions', function ($row) {
                if ($row->isDefault()) {
                    return '';
                }

                $deleteButton = '<button type="button" class="btn btn-sm btn-outline-danger deleteRow" id="'.$row->id.'" title="'.e(__('frontend.str.remove')).'"><i class="fa-solid fa-trash"></i></button>';

                return '<div class="d-flex justify-content-end gap-1 text-nowrap">'
                    .'<a class="btn btn-sm btn-outline-primary" title="'.e(__('frontend.str.edit')).'" href="'.route('admin.projects.edit', ['id' => $row->id]).'"><i class="fa-solid fa-pen-to-square"></i></a>'
                    .$deleteButton.'</div>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * Return email template rows formatted for the templates DataTable.
     *
     * @throws \Exception
     */
    public function getTemplates(): JsonResponse
    {
        $rows = ProjectAccess::scope(Templates::query(), 'manage')
            ->with(['attach', 'project'])
            ->select('templates.*');

        return DataTables::of($rows)
            ->addColumn('project', fn ($row) => $row->project?->name)
            ->addColumn('checkbox', fn ($row) => sprintf(
                '<input type="checkbox" class="form-check-input check" value="%d" name="templateId[]">',
                $row->id
            ))
            ->addColumn('action', function ($row) {
                $showBtn = sprintf(
                    '<a title="%s" class="btn btn-sm btn-outline-info" href="%s"><span class="fa fa-eye"></span></a>',
                    __('frontend.str.template'),
                    route('admin.templates.show', ['id' => $row->id])
                );

                $editBtn = sprintf(
                    '<a title="%s" class="btn btn-sm btn-outline-primary" href="%s"><span class="fa fa-edit"></span></a>',
                    __('frontend.str.edit'),
                    route('admin.templates.edit', ['id' => $row->id])
                );

                return '<div class="d-flex justify-content-center gap-1 text-nowrap">'.$showBtn.$editBtn.'</div>';
            })
            ->editColumn('name', function ($row) {
                $body = preg_replace('/(<.*?>)|(&.*?;)/', '', $row->body);

                return e($row->name).'<br><br><small class="text-muted">'.
                    e(StringHelper::shortText($body ?? '', 500)).
                    '</small>';
            })
            ->editColumn('prior', fn ($row) => sprintf(
                '<span class="badge %s">%s</span>',
                e($row->getPriority()->badgeClass()),
                e($row->getPrior())
            ))
            ->addColumn('attach', fn ($row) => sprintf(
                '<span class="badge %s">%s</span>',
                $row->attach->isNotEmpty() ? 'text-bg-success' : 'text-bg-secondary',
                e($row->attach->isNotEmpty() ? __('frontend.str.yes') : __('frontend.str.no'))
            ))
            ->editColumn('created_at', fn ($row) => $this->formatDateTime($row->created_at))
            ->rawColumns(['action', 'name', 'checkbox', 'prior', 'attach'])
            ->make(true);
    }

    /**
     * Return category rows with subscriber counts and action buttons for DataTables.
     *
     * @throws \Exception
     */
    public function getCategory(): JsonResponse
    {
        $rows = ProjectAccess::categories(Category::query(), 'manage')
            ->selectRaw('categories.id, categories.name, projects.name AS project, count(subscriptions.category_id) AS subcount')
            ->leftJoinSub(Project::query()->includingDefault()->select('projects.*'), 'projects', 'categories.project_id', '=', 'projects.id')
            ->leftJoin('subscriptions', 'categories.id', '=', 'subscriptions.category_id')
            ->groupBy('categories.id', 'categories.name', 'projects.name');

        return DataTables::of($rows)
            ->editColumn('project', fn ($row) => $row->project ?? __('frontend.str.projects.subscriber_unassigned'))
            ->addColumn('actions', function ($row) {
                $editBtn = sprintf(
                    '<a title="%s" class="btn btn-sm btn-outline-primary" href="%s"><span class="fa fa-edit"></span></a>',
                    __('frontend.str.edit'),
                    route('admin.category.edit', ['id' => $row->id])
                );

                $deleteBtn = sprintf(
                    '<button type="button" title="%s" class="btn btn-sm btn-outline-danger deleteRow" id="%d"><span class="fa fa-trash"></span></button>',
                    __('frontend.str.remove'),
                    $row->id
                );

                return '<div class="d-flex justify-content-center gap-1 text-nowrap">'.$editBtn.$deleteBtn.'</div>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * Return SMTP account rows with status, checkbox, and action columns for DataTables.
     *
     * @throws \Exception
     */
    public function getSmtp(): JsonResponse
    {
        $rows = Smtp::query();

        return DataTables::of($rows)
            ->addColumn('checkbox', fn ($row) => sprintf(
                '<input type="checkbox" class="form-check-input check" value="%d" name="activate[]">',
                $row->id
            ))
            ->editColumn('active', fn ($row) => $row->active === 1
                ? __('frontend.str.yes')
                : __('frontend.str.no'))
            ->editColumn('activeStatus', fn ($row) => $row->active)
            ->addColumn('action', function ($row) {
                $showBtn = sprintf(
                    '<a title="%s" class="btn btn-sm btn-outline-info" href="%s"><span class="fa fa-eye"></span></a>',
                    __('frontend.str.smtp_server'),
                    route('admin.smtp.show', ['id' => $row->id])
                );

                $editBtn = sprintf(
                    '<a title="%s" class="btn btn-sm btn-outline-primary" href="%s"><span class="fa fa-edit"></span></a>',
                    __('frontend.str.edit'),
                    route('admin.smtp.edit', ['id' => $row->id])
                );

                $deleteBtn = sprintf(
                    '<button type="button" class="btn btn-sm btn-outline-danger deleteRow" id="%d"><span class="fa fa-trash"></span></button>',
                    $row->id
                );

                return '<div class="d-flex justify-content-center gap-1 text-nowrap">'.$showBtn.$editBtn.$deleteBtn.'</div>';
            })
            ->editColumn('created_at', fn ($row) => $this->formatDateTime($row->created_at))
            ->rawColumns(['action', 'checkbox'])
            ->make(true);
    }

    /**
     * Return subscriber rows with category names, status, and action columns for DataTables.
     *
     * @throws \Exception
     */
    public function getSubscribers(): JsonResponse
    {
        $visibleProjectIds = ProjectAccess::projects()->select('projects.id');
        $rows = ProjectAccess::subscribers(Subscribers::query())
            ->with([
                'projects' => fn ($query) => $query->whereIn('projects.id', $visibleProjectIds)->select('projects.id', 'projects.name')->orderBy('projects.name'),
                'subscriptions' => fn ($query) => $query
                    ->select('subscriber_id', 'category_id')
                    ->whereHas('category', fn ($categories) => $categories->whereIn('project_id', $visibleProjectIds)),
                'subscriptions.category:id,name',
            ])
            ->select([
                'subscribers.id',
                'subscribers.name',
                'subscribers.email',
                'subscribers.active',
                'subscribers.created_at',
            ]);

        return DataTables::of($rows)
            ->whitelist(['id', 'name', 'email', 'active', 'created_at', 'subscribers.id', 'subscribers.name', 'subscribers.email', 'subscribers.active', 'subscribers.created_at'])
            ->addColumn('projects', fn ($row) => $row->projects->isEmpty()
                ? __('frontend.str.projects.subscriber_unassigned')
                : $row->projects->pluck('name')->implode(', '))
            ->addColumn('checkbox', fn ($row) => sprintf(
                '<input type="checkbox" class="form-check-input check" value="%d" name="activate[]">',
                $row->id
            ))
            ->addColumn('subscriptions', function ($row) {
                return $row->subscriptions
                    ->map(fn ($subscription) => $subscription->category?->name)
                    ->filter()
                    ->unique()
                    ->implode(', ');
            })
            ->editColumn('active', fn ($row) => $row->active === 1
                ? __('frontend.str.yes')
                : __('frontend.str.no'))
            ->editColumn('activeStatus', fn ($row) => $row->active)
            ->addColumn('action', function ($row) {
                $editBtn = sprintf(
                    '<a title="%s" class="btn btn-sm btn-outline-primary" href="%s"><span class="fa fa-edit"></span></a>',
                    __('frontend.str.edit'),
                    route('admin.subscribers.edit', ['id' => $row->id])
                );

                $deleteBtn = sprintf(
                    '<button type="button" title="%s" class="btn btn-sm btn-outline-danger deleteRow" id="%d"><span class="fa fa-trash"></span></button>',
                    __('frontend.str.remove'),
                    $row->id
                );

                return '<div class="d-flex justify-content-center gap-1 text-nowrap">'.$editBtn.$deleteBtn.'</div>';
            })
            ->editColumn('created_at', fn ($row) => $this->formatDateTime($row->created_at))
            ->rawColumns(['action', 'checkbox'])
            ->make(true);
    }

    /**
     * Return admin user rows with role labels and action buttons for DataTables.
     *
     * @throws \Exception
     */
    public function getUsers(): JsonResponse
    {
        $rows = User::query();

        return DataTables::of($rows)
            ->addColumn('action', function ($row) {
                $editBtn = sprintf(
                    '<a title="%s" class="btn btn-sm btn-outline-primary" href="%s"><span class="fa fa-edit"></span></a>',
                    __('frontend.str.edit'),
                    route('admin.users.edit', ['id' => $row->id])
                );

                $deleteBtn = (int) $row->id !== (int) Auth::id()
                    ? sprintf(
                        '<button type="button" title="%s" class="btn btn-sm btn-outline-danger deleteRow" id="%d"><span class="fa fa-trash"></span></button>',
                        __('frontend.str.remove'),
                        $row->id
                    )
                    : '';

                return '<div class="d-flex justify-content-center gap-1 text-nowrap">'.$editBtn.$deleteBtn.'</div>';
            })
            ->editColumn('role', function ($row) {
                $role = UserRole::tryFrom($row->role);

                return $role
                    ? sprintf('<span class="badge %s">%s</span>', e($role->badgeClass()), e($role->label()))
                    : e($row->role_label);
            })
            ->editColumn('created_at', fn ($row) => $this->formatDateTime($row->created_at))
            ->rawColumns(['action', 'role'])
            ->make(true);
    }

    /**
     * Return mailing summary rows for the log overview DataTable.
     *
     * @throws \Exception
     */
    public function getLogs(): JsonResponse
    {
        $rows = Logs::query()
            ->selectRaw(
                'logs.id, logs.time AS event_start, '.
                'COUNT(ready_sent.id) AS count, '.
                'COALESCE(SUM(ready_sent.success = 1), 0) AS sent, '.
                'COALESCE(SUM(ready_sent.readMail = 1), 0) AS read_mail'
            )
            ->join('ready_sent', 'logs.id', '=', 'ready_sent.log_id')
            ->groupBy('logs.id', 'logs.time');

        ProjectAccess::scope($rows, 'view', 'ready_sent.project_id');

        return DataTables::of($rows)
            ->editColumn('count', fn ($row) => sprintf(
                '<a href="%s">%s</a>',
                route('admin.log.info', ['id' => $row->id]),
                $row->count
            ))
            ->addColumn('unsent', fn ($row) => (int) $row->count - (int) $row->sent)
            ->editColumn('read_mail', fn ($row) => $row->read_mail ?? 0)
            ->addColumn('report', fn ($row) => (int) $row->count > 0
                ? sprintf(
                    '<a href="%s">%s</a>',
                    route('admin.log.report', ['id' => $row->id]),
                    __('frontend.str.download')
                )
                : '')
            ->editColumn('event_start', fn ($row) => $this->formatDateTime($row->event_start))
            ->rawColumns(['count', 'report'])
            ->make(true);
    }

    /**
     * Return per-recipient delivery log rows, optionally filtered by mailing-log ID.
     *
     * @throws \Exception
     */
    public function getInfoLog(?int $id = null): JsonResponse
    {
        $rows = $id
            ? ProjectAccess::scope(ReadySent::query())->where('log_id', $id)
            : ProjectAccess::scope(ReadySent::query());

        if ($id) {
            abort_unless((clone $rows)->exists(), 404);
        }

        return DataTables::of($rows)
            ->editColumn('success', fn ($row) => $row->success === 1
                ? __('frontend.str.send_status_yes')
                : __('frontend.str.send_status_no'))
            ->editColumn('readMail', fn ($row) => $row->readMail === 1
                ? __('frontend.str.yes')
                : __('frontend.str.no'))
            ->addColumn('status', fn ($row) => $row->success)
            ->addColumn('read', fn ($row) => $row->readMail)
            ->editColumn('created_at', fn ($row) => $this->formatDateTime($row->created_at))
            ->make(true);
    }

    /**
     * Return grouped redirect tracking rows with report links for DataTables.
     *
     * @throws \Exception
     */
    public function getRedirectLogs(): JsonResponse
    {
        $rows = ProjectAccess::scope(Redirect::query())
            ->selectRaw('url, COUNT(email) as count')
            ->groupBy('url')
            ->distinct();

        return DataTables::of($rows)
            ->editColumn('count', fn ($row) => sprintf(
                '<a href="%s">%s</a>',
                route('admin.redirect.info', ['url' => $this->encodeRouteBase64($row->url)]),
                $row->count
            ))
            ->addColumn('report', fn ($row) => sprintf(
                    '<a href="%s">%s</a>',
                    route('admin.redirect.report', ['url' => $this->encodeRouteBase64($row->url)]),
                    __('frontend.str.download')
                ))
            ->rawColumns(['count', 'report'])
            ->make(true);
    }

    /**
     * Return redirect tracking details for a single encoded URL.
     *
     * @throws \Exception
     */
    public function getInfoRedirectLog(string $url): JsonResponse
    {
        $decodedUrl = $this->decodeRouteBase64($url);

        $rows = ProjectAccess::scope(Redirect::query())->where('url', $decodedUrl);

        return DataTables::of($rows)
            ->editColumn('created_at', fn ($row) => $this->formatDateTime($row->created_at))
            ->make(true);
    }

    /**
     * Encode binary-safe base64 for a single route segment.
     */
    private function encodeRouteBase64(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    /**
     * Decode URL-safe or regular base64 route parameters.
     */
    private function decodeRouteBase64(string $value): string
    {
        $normalized = strtr($value, '-_', '+/');
        $padding = strlen($normalized) % 4;

        if ($padding > 0) {
            $normalized .= str_repeat('=', 4 - $padding);
        }

        return base64_decode($normalized, true) ?: '';
    }

    /**
     * Return macro rows with action buttons for the macros DataTable.
     *
     * @throws \Exception
     */
    public function getMacros(): JsonResponse
    {
        $rows = Macros::query();

        return DataTables::of($rows)
            ->addColumn('actions', function ($row) {
                $editBtn = sprintf(
                    '<a title="%s" class="btn btn-sm btn-outline-primary" href="%s"><span class="fa fa-edit"></span></a>',
                    __('frontend.str.edit'),
                    route('admin.macros.edit', ['id' => $row->id])
                );

                $deleteBtn = sprintf(
                    '<button type="button" title="%s" class="btn btn-sm btn-outline-danger deleteRow" id="%d"><span class="fa fa-trash"></span></button>',
                    __('frontend.str.remove'),
                    $row->id
                );

                return '<div class="d-flex justify-content-center gap-1 text-nowrap">'.$editBtn.$deleteBtn.'</div>';
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    /**
     * Normalize database or date-like values for display in DataTables.
     */
    private function formatDateTime(mixed $value): string
    {
        if (empty($value)) {
            return '';
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        $timestamp = strtotime((string) $value);

        return $timestamp !== false
            ? date('Y-m-d H:i:s', $timestamp)
            : (string) $value;
    }
}
