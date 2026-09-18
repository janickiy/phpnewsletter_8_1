<?php

namespace App\Http\Requests\Frontend;

use App\Models\Project;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AddSubRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('email'))) {
            $this->merge(['email' => strtolower(trim($this->input('email')))]);
        }
    }

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
            'project_id' => ['required', 'integer', Rule::in(Project::query()->includingDefault()->where('status', 1)->pluck('id')->all())],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('subscribers', 'email')->where(function (Builder $query): void {
                $query->whereExists(function (Builder $memberships): void {
                    $memberships->selectRaw('1')
                        ->from('project_subscriber')
                        ->whereColumn('project_subscriber.subscriber_id', 'subscribers.id')
                        ->where('project_subscriber.project_id', (int) $this->input('project_id'));
                });
            })],
            'name' => ['nullable', 'string', 'max:255'],
            'categoryId' => ['nullable', 'array'],
            'categoryId.*' => ['integer', 'distinct', Rule::exists('categories', 'id')->where('project_id', (int) $this->input('project_id'))],
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
