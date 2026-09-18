<?php

namespace App\Http\Requests\Admin\Subscribers;

use App\Models\Project;
use App\Services\ProjectAccess;
use Illuminate\Validation\Rule;

trait ProjectRules
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => strtolower(trim($this->input('email')))]);
        }
    }

    private function projectRules(bool $requireProject = true, bool $defaultProject = false): array
    {
        $projectIds = (array) $this->input('project_ids', []);
        if ($defaultProject && $projectIds === []) {
            $projectIds = [Project::DEFAULT_ID];
        }

        return [
            'project_ids' => $requireProject && !$defaultProject && !$this->user()?->isAdmin()
                ? ['required', 'array', 'min:1'] : ['nullable', 'array'],
            'project_ids.*' => [
                'required', 'integer', 'distinct',
                Rule::in(ProjectAccess::projects('view', $this->user())->pluck('projects.id')->all()),
            ],
            'categoryId' => ['nullable', 'array'],
            'categoryId.*' => [
                'required', 'integer', 'distinct',
                Rule::exists('categories', 'id')->whereIn('project_id', array_filter($projectIds, 'is_numeric')),
            ],
        ];
    }
}
