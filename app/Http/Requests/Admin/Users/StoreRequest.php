<?php

namespace App\Http\Requests\Admin\Users;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === User::ROLE_ADMIN;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login' => [
                'required',
                'string',
                'min:3',
                'max:255',
                'unique:users,login',
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'role' => [
                'required',
                'string',
                Rule::in(UserRole::values()),
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'password' => [
                'required',
                'string',
                'min:6',
            ],
            'password_again' => [
                'required',
                'string',
                'min:6',
                'same:password',
            ],
        ];
    }
}
