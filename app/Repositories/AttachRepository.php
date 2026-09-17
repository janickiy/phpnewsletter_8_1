<?php

namespace App\Repositories;

use App\Models\Attach;
use App\Services\ProjectAccess;

class AttachRepository extends BaseRepository
{
    /**
     * Initialize attachment persistence with the attachment model.
     */
    public function __construct(Attach $model)
    {
        parent::__construct($model);
    }

    /**
     * Delete an attachment's stored file and database record by identifier.
     *
     * @param int $id
     * @return bool
     */
    public function remove(int $id): bool
    {
        $model = $this->model->find($id);

        if (!$model) {
            return false;
        }

        ProjectAccess::authorizeProject((int) $model->template?->project_id, 'manage');
        $model->remove();

        return true;
    }
}
