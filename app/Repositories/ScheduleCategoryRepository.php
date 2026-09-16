<?php

namespace App\Repositories;

use App\Models\ScheduleCategory;


class ScheduleCategoryRepository extends BaseRepository
{
    /**
     * Initialize schedule-category persistence with its junction model.
     */
    public function __construct(ScheduleCategory $model)
    {
        parent::__construct($model);
    }

    /**
     * Delete every category association for the supplied schedule.
     */
    public function removeByScheduleId(int $scheduleId): bool
    {
        return $this->model->where('schedule_id', $scheduleId)->delete();
    }
}
