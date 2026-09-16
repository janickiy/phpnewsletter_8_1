<?php

namespace App\Http\Requests\Frontend;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AddSubRequest extends FormRequest
{
    /**
     * Allow public visitors to submit the newsletter subscription form.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Define validation rules for a new public newsletter subscription.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc', 'max:255', 'unique:subscribers,email'],
            'name' => ['nullable', 'string', 'max:255'],
            'categoryId' => ['nullable', 'array'],
            'categoryId.*' => ['integer', 'exists:categories,id'],
        ];
    }

    /**
     * Return subscription validation failures as the JSON format expected by the public form.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            response()->json([
                'errors' => $validator->errors(),
                'result' => 'errors',
            ], 422)
        );
    }
}
