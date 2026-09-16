<?php

namespace App\Http\Requests\Frontend;

use Illuminate\Foundation\Http\FormRequest;

class InstallAdminRequest extends FormRequest
{
    /**
     * Allow administrator credentials to be submitted during installation.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Define validation rules for the initial administrator credentials.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:6'],
            'confirm_password' => ['required', 'string', 'min:6', 'same:password'],
        ];
    }
}
