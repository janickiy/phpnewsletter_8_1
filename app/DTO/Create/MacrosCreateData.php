<?php

namespace App\DTO\Create;

final class MacrosCreateData
{
    /**
     * Capture the name, replacement value, and type of a new macro.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $value,
        public readonly int $type,
    ) {
    }

    /**
     * Convert the macro creation data into persistence attributes.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'value' => $this->value,
            'type' => $this->type,
        ];
    }
}
