<?php

namespace App\DTO\Update;

final class SettingsUpdateData
{
    /**
     * Capture the complete set of application setting values to update.
     */
    public function __construct(
        private readonly array $values,
    ) {
    }

    /**
     * Return the setting values in their persistence-ready form.
     */
    public function toArray(): array
    {
        return $this->values;
    }
}
