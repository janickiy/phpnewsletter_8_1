<?php

namespace App\DTO\Update;

final class SettingsUpdateData
{
    /**
     * Capture the complete set of application setting values to update.
     */
    public function __construct(
        private readonly array $values,
    ) {}

    /**
     * Return the setting values in their persistence-ready form.
     */
    public function toArray(): array
    {
        // Ignore retired keys even when older clients submit differently cased names.
        return array_filter(
            $this->values,
            static fn (string $key): bool => ! in_array(str_replace(' ', '_', strtoupper($key)), [
                'CHARSET',
                'RANDOM_SEND',
                'RENDOM_REPLACEMENT_SUBJECT',
                'RANDOM_REPLACEMENT_BODY',
            ], true),
            ARRAY_FILTER_USE_KEY,
        );
    }
}
