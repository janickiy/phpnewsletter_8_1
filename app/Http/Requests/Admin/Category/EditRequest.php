<?php

namespace App\Http\Requests\Admin\Category;

use App\Models\Category;
use App\Services\ProjectAccess;
use Illuminate\Foundation\Http\FormRequest;
use App\Models\User;

class EditRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->role === User::ROLE_ADMIN
            && ProjectAccess::categories(Category::query(), 'manage', $this->user())
                ->whereKey($this->integer('id'))->exists();
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
                'exists:' . Category::getTableName() . ',id',
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
        ];
    }
}
