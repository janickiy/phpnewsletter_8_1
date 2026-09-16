<?php

namespace App\Http\Controllers\Admin;


use App\DTO\Create\ScheduleCreateData;
use App\DTO\Update\ScheduleUpdateData;
use App\Http\Requests\Admin\Schedule\EditRequest;
use App\Http\Requests\Admin\Schedule\StoreRequest;
use App\Models\Schedule;
use App\Repositories\CategoryRepository;
use App\Repositories\ScheduleRepository;
use App\Repositories\TemplateRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    /**
     * Inject repositories required to manage scheduled mailings and their form options.
     */
    public function __construct(
        private readonly ScheduleRepository $scheduleRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly TemplateRepository $templateRepository,
    ) {
        parent::__construct();
    }

    /**
     * Show the scheduled mailing calendar page.
     *
     * @return View
     */
    public function index(): View
    {
        return view('admin.schedule.index', [
            'schedule' => Schedule::query()->get(),
            'infoAlert' => __('frontend.hint.schedule_index'),
            'title' => __('frontend.title.schedule_index'),
        ]);
    }

    /**
     * Return scheduled mailing events filtered by the requested calendar date range.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        return response()->json(
            $this->scheduleRepository->getScheduleByDateInterval($request)
        );
    }

    /**
     * Handle inline calendar edits or deletions sent by FullCalendar.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function calendarEvents(Request $request): JsonResponse
    {
        $event = Schedule::query()->find($request->id);

        if (!$event) {
            return response()->json(false, 404);
        }

        return match ($request->type) {
            'edit' => response()->json(
                $event->update([
                    'event_name' => $request->event_name,
                    'event_start' => $request->event_start,
                    'event_end' => $request->event_end,
                ])
            ),
            'delete' => response()->json($event->delete()),
            default => response()->json(false, 400),
        };
    }

    /**
     * Show the form used to create a scheduled mailing.
     *
     * @return View
     */
    public function create(): View
    {
        return view('admin.schedule.create_edit', [
            'options' => $this->templateRepository->getOption(),
            'category_options' => $this->categoryRepository->getOption(),
            'infoAlert' => __('frontend.hint.schedule_create'),
            'title' => __('frontend.title.schedule_index'),
        ]);
    }

    /**
     * Validate and persist a new scheduled mailing.
     *
     * @param StoreRequest $request
     * @return RedirectResponse
     */
    public function store(StoreRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();

            $this->scheduleRepository->add(
                new ScheduleCreateData(
                    eventName: $data['event_name'],
                    eventStart: $data['event_start'],
                    eventEnd: $data['event_end'],
                    templateId: (int) $data['template_id'],
                    categoryIds: $data['categoryId'],
                )
            );
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return to_route('admin.schedule.index')->with('success', __('message.information_successfully_added'));
    }

    /**
     * Show the edit form for an existing scheduled mailing.
     *
     * @param int $id
     * @return View
     */
    public function edit(int $id): View
    {
        $row = $this->scheduleRepository->find($id);

        abort_if(!$row, 404);

        return view('admin.schedule.create_edit', [
            'categoryId' => $row->categories?->pluck('id')->toArray() ?? [],
            'options' => $this->templateRepository->getOption(),
            'category_options' => $this->categoryRepository->getOption(),
            'row' => $row,
            'infoAlert' => __('frontend.hint.schedule_edit'),
            'date_interval' => date('d.m.Y H:i', strtotime($row->event_start)) . ' - ' . date('d.m.Y H:i', strtotime($row->event_end)),
            'title' => __('frontend.title.schedule_edit'),
        ]);
    }

    /**
     * Validate and save changes to an existing scheduled mailing.
     *
     * @param EditRequest $request
     * @return RedirectResponse
     */
    public function update(EditRequest $request): RedirectResponse
    {
        try {
            $data = $request->validated();

            $this->scheduleRepository->update(
                (int) $data['id'],
                new ScheduleUpdateData(
                    eventName: $data['event_name'],
                    eventStart: $data['event_start'],
                    eventEnd: $data['event_end'],
                    templateId: (int) $data['template_id'],
                    categoryIds: $data['categoryId'],
                )
            );
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->with('error', $e->getMessage())
                ->withInput();
        }

        return to_route('admin.schedule.index')->with('success', __('message.data_updated'));
    }

    /**
     * Delete a scheduled mailing for AJAX-driven calendar actions.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->scheduleRepository->remove($id);

        if (!$deleted) {
            return response()->json([
                'success' => false,
            ], 404);
        }

        return response()->json([
            'success' => true,
            'id' => $id,
        ]);
    }
}
