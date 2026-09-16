<?php

namespace App\Http\Requests\Admin\Smtp;

use Illuminate\Foundation\Http\FormRequest;

class StatusRequest extends FormRequest
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
            'activate' => [
                'required',
                'array',
                'min:1',
            ],

            'activate.*' => [
                'required',
                'integer',
            ],

            'action' => [
                'required',
                'integer',
            ],
        ];
    }
}
