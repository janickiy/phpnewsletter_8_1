<?php

namespace App\Repositories;

use App\DTO\Create\RedirectCreateData;
use App\Models\Redirect;

class RedirectRepository extends BaseRepository
{
    /**
     * Initialize redirect-tracking persistence with the redirect model.
     */
    public function __construct(Redirect $model)
    {
        parent::__construct($model);
    }

    /**
     * Record one tracked-link visit from a newsletter recipient.
     *
     * @param RedirectCreateData $data
     * @return Redirect
     */
    public function add(RedirectCreateData $data): Redirect
    {
        return $this->model->query()->create($data->toArray());
    }
}
