<?php

namespace App\Http\Requests\Admin\Templates;

use App\Models\Project;
use Illuminate\Foundation\Http\FormRequest;
use App\Services\ProjectAccess;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->input('project_id') === null || $this->input('project_id') === '') {
            $this->merge(['project_id' => Project::DEFAULT_ID]);
        }
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'project_id' => ['required', 'integer', Rule::in(ProjectAccess::projects('manage')->pluck('id')->all())],
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
                'in:0,1,2',
            ],
        ];
    }
}
