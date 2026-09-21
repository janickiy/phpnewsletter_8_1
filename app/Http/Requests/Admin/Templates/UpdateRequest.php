<?php

namespace App\Http\Requests\Admin\Templates;

use App\Enums\TemplatePriority;
use App\Models\Templates;
use Illuminate\Foundation\Http\FormRequest;
use App\Services\ProjectAccess;
use Illuminate\Validation\Rule;


class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return ProjectAccess::scope(Templates::query(), 'manage')->whereKey($this->integer('id'))->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'project_id' => ['required', 'integer', Rule::in(ProjectAccess::projects('manage')->pluck('id')->all()), Rule::in([Templates::query()->find($this->integer('id'))?->project_id])],
            'id' => [
                'required',
                'integer',
                'exists:' . Templates::getTableName() .',id',
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'body' => [
                'required',
                'string',
            ],
            'prior' => [
                'required',
                'integer',
                Rule::in(TemplatePriority::values()),
            ],
        ];
    }
}
