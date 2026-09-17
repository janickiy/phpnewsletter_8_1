<?php

namespace App\Http\Requests\Admin\Projects;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->isAdmin() || $user->isProjectAdmin())
            && ($user->isAdmin() || !$this->exists('owner_id'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'status' => ['required', 'boolean'],
            'owner_id' => ['sometimes', 'required', 'integer', Rule::exists('users', 'id')],
            'project_admin_ids_present' => ['sometimes', 'accepted'],
            'project_admin_ids' => ['sometimes', 'array'],
            'project_admin_ids.*' => ['required', 'integer', 'distinct', Rule::exists('users', 'id')->where('role', User::ROLE_PROJECT_ADMIN)],
            'moderator_ids_present' => ['sometimes', 'accepted'],
            'moderator_ids' => ['sometimes', 'array'],
            'moderator_ids.*' => ['required', 'integer', 'distinct', Rule::exists('users', 'id')->where('role', User::ROLE_MODERATOR)],
        ];
    }
}
