<?php

namespace App\Http\Requests\Admin\Subscribers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class ImportRequest extends FormRequest
{
    private const ALLOWED_EXTENSIONS = ['csv', 'xlsx', 'xls', 'ods', 'txt'];
    private const MAX_IMPORT_FILE_SIZE_KB = 262144;

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
            'import' => [
                'required',
                'file',
                'max:' . self::MAX_IMPORT_FILE_SIZE_KB,
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (!$value instanceof UploadedFile) {
                        return;
                    }

                    $extension = strtolower($value->getClientOriginalExtension());

                    if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                        $fail(__('validation.mimes', [
                            'attribute' => $attribute,
                            'values' => implode(', ', self::ALLOWED_EXTENSIONS),
                        ]));
                    }
                },
            ],
        ];
    }
}
