<?php

namespace App\Repositories;

use App\DTO\Create\ScheduleCreateData;
use App\DTO\Update\ScheduleUpdateData;
use App\Models\Schedule;
use App\Models\Templates;
use App\Services\ProjectAccess;
use App\Models\ScheduleCategory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class ScheduleRepository extends BaseRepository
{
    /**
     * Initialize schedule persistence and its transaction manager.
     */
    public function __construct(
        Schedule $model,
        private readonly DatabaseManager $database
    ) {
        parent::__construct($model);
    }

    /**
     * Create a mailing schedule and synchronize its target categories atomically.
     *
     * @throws \Throwable
     */
    public function add(ScheduleCreateData $data): Schedule
    {
        return $this->database->transaction(function () use ($data) {
            $model = $this->create($this->mapping($data->toArray()));

            $this->syncCategories($model->id, $data->categoryIds);

            return $model;
        });
    }

    /**
     * Update a mailing schedule and replace its target categories atomically.
     *
     * @param int $id
     * @param ScheduleUpdateData $data
     * @return bool
     * @throws \Throwable
     */
    public function update(int $id, ScheduleUpdateData $data): bool
    {
        abort_unless($this->find($id), 404);

        return $this->database->transaction(function () use ($id, $data) {
            ScheduleCategory::where('schedule_id', $id)->delete();

            $this->syncCategories($id, $data->categoryIds);

            return $this->updateModel($id, $this->mapping($data->toArray()));
        });
    }

    /**
     * Remove a schedule and its category associations in one transaction.
     *
     * @param int $id
     * @return bool|null
     * @throws \Throwable
     */
    public function removeSchedule(int $id): ?bool
    {
        return $this->database->transaction(function () use ($id) {
            $model = $this->find($id);

            if (!$model) {
                return false;
            }

            ScheduleCategory::where('schedule_id', $id)->delete();

            return $model->delete();
        });
    }

    /**
     * Return schedules whose configured time window includes the current time.
     *
     * @return Collection|null
     */
    public function getScheduleEvent(): ?Collection
    {
        return $this->model
            ->with('template.project')
            ->whereHas('project', fn ($query) => $query->where('status', 1))
            ->whereHas('template', fn ($query) => $query->whereColumn('templates.project_id', 'schedule.project_id'))
            ->where('event_start', '<=', Carbon::now()->toDateTimeString())
            ->where('event_end', '>=', Carbon::now()->toDateTimeString())
            ->get();
    }

    /**
     * Build calendar event payloads for schedules contained in a requested date range.
     *
     * @param Request $request
     * @return array
     */
    public function getScheduleByDateInterval(Request $request): array
    {
        $rows = ProjectAccess::scope(Schedule::query(), 'manage')
            ->whereDate('event_start', '>=', $request->start)
            ->whereDate('event_end', '<=', $request->end)
            ->get(['id', 'event_name', 'event_start', 'event_end']);

        $items = [];

        foreach ($rows as $row) {
            $items[] = [
                'id' => $row->id,
                'start' => $row->event_start,
                'end' => $row->event_end,
                'title' => $row->event_name,
            ];
        }

        return $items;
    }

    /**
     * Delete a schedule and its category associations by identifier.
     *
     * @param int $id
     * @return bool|null
     * @throws \Throwable
     */
    public function remove(int $id): ?bool
    {
        abort_unless($this->find($id), 404);

        return $this->database->transaction(function () use ($id) {
            ScheduleCategory::where('schedule_id', $id)->delete();

            return $this->delete($id);
        });
    }

    public function find(int $id): ?Schedule
    {
        return ProjectAccess::scope(Schedule::query(), 'manage')->find($id);
    }

    /**
     * Create category junction records for a mailing schedule.
     *
     * @param int $scheduleId
     * @param array $categoryIds
     * @return void
     */
    private function syncCategories(int $scheduleId, array $categoryIds): void
    {
        foreach ($categoryIds as $categoryId) {
            if (is_numeric($categoryId)) {
                ScheduleCategory::create([
                    'schedule_id' => $scheduleId,
                    'category_id' => (int) $categoryId,
                ]);
            }
        }
    }

    /**
     * Normalize schedule dates and restrict input to fillable model attributes.
     *
     * @param array $data
     * @return array
     */
    private function mapping(array $data): array
    {
        [$eventStart, $eventEnd] = $this->resolveEventDates($data);
        $template = ProjectAccess::scope(Templates::query(), 'manage')->findOrFail($data['template_id']);

        return collect($data)
            ->merge([
                'event_start' => $eventStart,
                'event_end' => $eventEnd,
                'project_id' => $template->project_id,
            ])
            ->only($this->model->getFillable())
            ->map(function ($value, $key) {
                return match ($key) {
                    'template_id' => !is_null($value) ? (int) $value : null,
                    default => $value,
                };
            })
            ->all();
    }


    /**
     * Parse schedule start and end timestamps from normalized fields or a combined interval.
     *
     * @param array $data
     * @return array|null[]
     */
    private function resolveEventDates(array $data): array
    {
        if (
            !empty($data['event_start']) &&
            !empty($data['event_end'])
        ) {
            return [
                Carbon::createFromFormat('d.m.Y H:i', $data['event_start'])->format('Y-m-d H:i:s'),
                Carbon::createFromFormat('d.m.Y H:i', $data['event_end'])->format('Y-m-d H:i:s'),
            ];
        }

        if (!empty($data['date_interval']) && str_contains($data['date_interval'], ' - ')) {
            [$eventStart, $eventEnd] = explode(' - ', $data['date_interval'], 2);

            return [
                Carbon::createFromFormat('d.m.Y H:i', $eventStart)->format('Y-m-d H:i:s'),
                Carbon::createFromFormat('d.m.Y H:i', $eventEnd)->format('Y-m-d H:i:s'),
            ];
        }

        return [null, null];
    }
}
