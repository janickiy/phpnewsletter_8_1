<?php

namespace App\Repositories;


use App\Enums\ProcessStatus;
use App\Models\Process;

class ProcessRepository extends BaseRepository
{
    /**
     * Initialize process-state persistence with the process model.
     */
    public function __construct(Process $model)
    {
        parent::__construct($model);
    }

    /**
     * Store the current background-process command for a user, creating the state when absent.
     *
     * @param int $user_id
     * @param string $command
     * @return bool
     */
    public function updateByUserId(int $user_id, string $command): bool
    {
        $model = $this->model->where('user_id', $user_id);

        if ($model->first()) {
            return $model->update(['command' => $command]);
        } else {
            $this->model->command = $command;
            $this->model->user_id = $user_id;
            return $this->model->save();
        }
    }

    /**
     * Return a user's current process state, initializing it to the start state when absent.
     *
     * @param int $user_id
     * @return string
     */
    public function getProcess(int $user_id): string
    {
        $model = $this->model->where('user_id', $user_id)->first();

        if ($model) {
            return $model->command;
        } else {
            $this->model->command = ProcessStatus::Start->value;
            $this->model->user_id = $user_id;
            $this->model->save();

            return 'start';
        }
    }
}
