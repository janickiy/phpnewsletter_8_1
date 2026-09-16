<?php

namespace App\Repositories;

use App\DTO\Create\MacrosCreateData;
use App\DTO\Update\MacrosUpdateData;
use App\Models\Macros;

class MacrosRepository extends BaseRepository
{
    /**
     * Initialize macro persistence with the macro model.
     */
    public function __construct(Macros $model)
    {
        parent::__construct($model);
    }

    /**
     * Create a macro from validated macro data.
     *
     * @param MacrosCreateData $data
     * @return Macros
     */
    public function add(MacrosCreateData $data): Macros
    {
        return $this->create($data->toArray());
    }

    /**
     * Update an existing macro with validated macro data.
     *
     * @param int $id
     * @param MacrosUpdateData $data
     * @return bool
     */
    public function update(int $id, MacrosUpdateData $data): bool
    {
        return $this->updateModel($id, $this->mapping($data->toArray()));
    }

    /**
     * Restrict macro input to attributes allowed for mass assignment.
     *
     * @param array $data
     * @return array
     */
    private function mapping(array $data): array
    {
        return collect($data)
            ->only($this->model->getFillable())
            ->all();
    }
}
