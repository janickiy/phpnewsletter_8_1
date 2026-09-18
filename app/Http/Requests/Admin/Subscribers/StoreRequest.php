<?php

namespace App\Http\Requests\Admin\Subscribers;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    use ProjectRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->projectRules(defaultProject: true) + [
            'name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
            ],
        ];
    }
}
