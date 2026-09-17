<?php

namespace App\Http\Requests\Admin\Schedule;


use App\Models\Category;
use App\Models\Schedule;
use App\Models\Templates;
use App\Services\ProjectAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EditRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return ProjectAccess::scope(Schedule::query(), 'manage')->whereKey($this->integer('id'))->exists();
    }

    /**
     * Split the submitted date interval into normalized schedule start and end fields.
     */
    protected function prepareForValidation(): void
    {
        $eventStart = null;
        $eventEnd = null;

        if (!empty($this->date_interval) && str_contains($this->date_interval, ' - ')) {
            $date = explode(' - ', $this->date_interval, 2);

            $eventStart = $date[0] ?? null;
            $eventEnd = $date[1] ?? null;
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
            'id' => [
                'required',
                'integer',
                Rule::exists(Schedule::getTableName(), 'id'),
            ],
            'event_name' => [
                'required',
                'string',
                'max:255',
            ],

            'template_id' => [
                'required',
                'integer',
                Rule::in(ProjectAccess::scope(Templates::query(), 'manage')->pluck('id')->all()),
            ],

            'categoryId' => [
                'required',
                'array',
                'min:1',
            ],

            'categoryId.*' => [
                'required',
                'integer',
                Rule::exists(Category::getTableName(), 'id')->where('project_id', ProjectAccess::scope(Templates::query(), 'manage')->whereKey($this->integer('template_id'))->value('project_id') ?? 0),
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
