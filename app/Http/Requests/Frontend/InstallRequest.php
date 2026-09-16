<?php

namespace App\Http\Requests\Frontend;

use Illuminate\Foundation\Http\FormRequest;

class InstallRequest extends FormRequest
{
    /**
     * Allow database connection settings to be submitted during installation.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Define validation rules for installer database connection settings.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'host' => ['required', 'string', 'max:255'],
            'database' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:4'],
            'prefix' => ['nullable', 'string', 'max:50'],
        ];
    }
}
