<?php

namespace App\Http\Requests\Admin\Projects;

use App\Services\ProjectAccess;
use Illuminate\Validation\Rule;

class UpdateRequest extends StoreRequest
{
    public function authorize(): bool
    {
        if (!$this->user()) {
            return false;
        }

        $project = ProjectAccess::authorizeProject($this->integer('id'), 'manage');
        if ($project->isDefault()) {
            return false;
        }
        $canAssignAdministrators = $this->user()->isAdmin() || $project->owner_id === $this->user()->id;

        return ($this->user()->isAdmin() || !$this->exists('owner_id'))
            && ($canAssignAdministrators || (!$this->exists('project_admin_ids') && !$this->exists('project_admin_ids_present')));
    }

    public function rules(): array
    {
        return [
            ...parent::rules(),
            'id' => ['required', 'integer', Rule::exists('projects', 'id')],
        ];
    }
}
