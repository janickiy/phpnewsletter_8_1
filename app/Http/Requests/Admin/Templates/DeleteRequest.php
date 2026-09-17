<?php

namespace App\Http\Requests\Admin\Templates;

use App\Models\Templates;
use Illuminate\Foundation\Http\FormRequest;
use App\Services\ProjectAccess;
use Illuminate\Validation\Rule;

class DeleteRequest extends FormRequest
{
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
            'templateId' => [
                'required',
                'array',
                'min:1',
            ],
            'templateId.*' => [
                'required',
                'integer',
                Rule::in(ProjectAccess::scope(Templates::query(), 'manage')->pluck('id')->all()),
            ],
            'action' => [
                'required',
                'integer',
                Rule::in([1]),
            ],
        ];
    }
}
