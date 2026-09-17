<?php

namespace App\Http\Requests\Admin\Subscribers;

use App\Models\Subscribers;
use App\Services\ProjectAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EditRequest extends FormRequest
{
    use ProjectRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return ProjectAccess::subscribers(Subscribers::query(), $this->user())->whereKey((int) $this->input('id'))->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->projectRules(false) + [
            'id' => [
                'required',
                'integer',
                Rule::exists(Subscribers::getTableName(), 'id'),
            ],
            'name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique(Subscribers::getTableName(), 'email')->ignore((int) $this->input('id')),
            ],
        ];
    }
}
