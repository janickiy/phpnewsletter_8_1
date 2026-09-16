<?php

namespace App\Repositories;

use App\DTO\Create\TemplatesCreateData;
use App\DTO\Update\TemplatesUpdateData;
use App\Models\Templates;

class TemplateRepository extends BaseRepository
{
    /**
     * Initialize template persistence with the template model.
     */
    public function __construct(Templates $model)
    {
        parent::__construct($model);
    }

    /**
     * Create an email template from normalized template data.
     *
     * @param TemplatesCreateData $data
     * @return Templates
     */
    public function add(TemplatesCreateData $data): Templates
    {
        return $this->create($this->mapping($data->toArray()));
    }

    /**
     * Update an email template with normalized template data.
     *
     * @param int $id
     * @param TemplatesUpdateData $data
     * @return bool
     */
    public function update(int $id, TemplatesUpdateData $data): bool
    {
        return $this->updateModel($id, $this->mapping($data->toArray()));
    }

    /**
     * Build alphabetically ordered template options keyed by identifier.
     *
     * @return array
     */
    public function getOption(): array
    {
        return $this->model->orderBy('name')->get()->pluck('name', 'id')->toArray();
    }

    /**
     * Apply the requested bulk action to selected templates.
     *
     * @param array $Ids
     * @param int $action
     * @return void
     */
    public function updateStatus(array $Ids, int $action): void
    {
        if ($action === 1) {
            $templates = $this->model->whereIN('id', $Ids)->get();

            foreach ($templates as $template) {
                $template->remove();
            }
        }
    }

    /**
     * Remove a template identified by the supplied primary key.
     *
     * @param int $id
     * @return void
     */
    public function remove(int $id)
    {
        $this->model->remove($id);
    }

    /**
     * Normalize template priority and restrict input to fillable attributes.
     *
     * @param array $data
     * @return array
     */
    private function mapping(array $data): array
    {
        return collect($data)
            ->only($this->model->getFillable())
            ->map(function ($value, $key) {
                if ($key === 'prior' && !is_null($value)) {
                    return (int)$value;
                }
                return $value;
            })
            ->all();
    }
}
