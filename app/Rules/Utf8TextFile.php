<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class Utf8TextFile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! in_array(strtolower($value->getClientOriginalExtension()), ['csv', 'txt'], true)) {
            return;
        }

        $stream = @fopen($value->getPathname(), 'rb');

        if ($stream === false) {
            $fail(__('validation.uploaded', ['attribute' => $attribute]));

            return;
        }

        $pending = '';

        try {
            while (! feof($stream)) {
                $chunk = fread($stream, 65536);

                if ($chunk === false) {
                    $fail(__('validation.uploaded', ['attribute' => $attribute]));

                    return;
                }

                $chunk = $pending.$chunk;

                // NUL bytes also reject ASCII-only UTF-16/32 files without a BOM.
                if (str_contains($chunk, "\0")) {
                    $fail(__('validation.utf8_file', ['attribute' => $attribute]));

                    return;
                }

                // A UTF-8 character may span two reads; retain at most three bytes.
                for ($tail = 0; $tail <= min(3, strlen($chunk)); $tail++) {
                    $length = strlen($chunk) - $tail;

                    if (mb_check_encoding(substr($chunk, 0, $length), 'UTF-8')) {
                        $pending = substr($chunk, $length);

                        continue 2;
                    }
                }

                $fail(__('validation.utf8_file', ['attribute' => $attribute]));

                return;
            }

            if (! mb_check_encoding($pending, 'UTF-8')) {
                $fail(__('validation.utf8_file', ['attribute' => $attribute]));
            }
        } finally {
            fclose($stream);
        }
    }
}
