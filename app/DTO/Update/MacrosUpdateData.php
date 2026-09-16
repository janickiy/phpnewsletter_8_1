<?php

namespace App\DTO\Update;

final class MacrosUpdateData
{
    /**
     * Capture the macro values that should replace the stored definition.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $value,
        public readonly int $type,
    ) {
    }

    /**
     * Convert the macro update data into persistence attributes.
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
