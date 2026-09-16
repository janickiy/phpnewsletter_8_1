<?php

namespace App\Http\Requests\Admin\Schedule;

use App\Models\Category;
use App\Models\Templates;
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
     * Split the submitted date interval into normalized schedule start and end fields.
     */
    protected function prepareForValidation(): void
    {
        $eventStart = null;
        $eventEnd = null;

        if (!empty($this->date_interval) && str_contains($this->date_interval, ' - ')) {
            [$eventStart, $eventEnd] = explode(' - ', $this->date_interval, 2);
        }

        $this->merge([
            'event_start' => $eventStart,
            'event_end' => $eventEnd,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'event_name' => [
                'required',
                'string',
                'max:255',
            ],

            'template_id' => [
                'required',
                'integer',
                Rule::exists(Templates::getTableName(), 'id'),
            ],

            'categoryId' => [
                'required',
                'array',
                'min:1',
            ],

            'categoryId.*' => [
                'required',
                'integer',
                Rule::exists(Category::getTableName(), 'id'),
            ],

            'event_start' => [
                'required',
                'date_format:d.m.Y H:i',
                'after:tomorrow',
            ],

            'event_end' => [
                'required',
                'date_format:d.m.Y H:i',
                'after:event_start',
            ],
        ];
    }
}
