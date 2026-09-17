<?php

namespace App\Repositories;

use App\DTO\Create\TemplatesCreateData;
use App\DTO\Update\TemplatesUpdateData;
use App\Models\Templates;
use App\Services\ProjectAccess;

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
        ProjectAccess::authorizeProject($data->projectId, 'manage');

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
        $template = $this->find($id);
        abort_unless($template && (int) $template->project_id === $data->projectId, 404);

        return $template->fill($this->mapping($data->toArray()))->save();
    }

    /**
     * Build alphabetically ordered template options keyed by identifier.
     *
     * @return array
     */
    public function getOption(): array
    {
        return ProjectAccess::scope(Templates::query(), 'manage')->with('project')->orderBy('name')->get()
            ->mapWithKeys(fn ($template) => [$template->id => $template->project->name . ' — ' . $template->name])->all();
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
            $templates = ProjectAccess::scope(Templates::query(), 'manage')->whereIn('id', $Ids)->get();
            abort_unless($templates->count() === count(array_unique($Ids)), 404);

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
        $template = $this->find($id);
        abort_unless($template, 404);
        $template->remove();
    }

    public function find(int $id): ?Templates
    {
        return ProjectAccess::scope(Templates::query(), 'manage')->find($id);
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
