<?php

namespace App\Http\Requests\Admin\Category;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\User;

class EditRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === User::ROLE_ADMIN;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $projectId = Category::query()->find((int) $this->input('id'))?->project_id;

        return [
            'project_id' => [$projectId === null ? 'nullable' : 'required', 'integer', Rule::in([$projectId])],
            'id' => [
                'required',
                'integer',
                'exists:' . Category::getTableName() . ',id',
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
        ];
    }
}
