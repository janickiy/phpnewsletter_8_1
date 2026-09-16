<?php

namespace App\Http\Requests\Admin\Subscribers;

use App\Models\Category;
use App\Models\Subscribers;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
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
            'name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique(Subscribers::getTableName(), 'email'),
            ],
            'categoryId' => [
                'nullable',
                'array',
            ],
            'categoryId.*' => [
                'required',
                'integer',
                Rule::exists(Category::getTableName(), 'id'),
            ],
        ];
    }
}
