<?php

namespace App\Http\Requests\Admin\Subscribers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportRequest extends FormRequest
{
    use ProjectRules;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return $this->projectRules() + [
            'export_type' => ['required', Rule::in(['text', 'excel'])],
            'compress' => ['required', Rule::in(['none', 'zip'])],
        ];
    }
}
