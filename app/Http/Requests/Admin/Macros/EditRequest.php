<?php

namespace App\Http\Requests\Admin\Macros;

use App\Models\Macros;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EditRequest extends FormRequest
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
            'id' => [
                'required',
                'integer',
                Rule::exists(Macros::getTableName(), 'id'),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-zA-Z0-9_]+$/',
                Rule::unique(Macros::getTableName(), 'name')->ignore($this->id),
            ],
            'value' => [
                'required',
                'string',
            ],
            'type' => [
                'required',
                'integer',
                'min:0',
            ],
        ];
    }
}
