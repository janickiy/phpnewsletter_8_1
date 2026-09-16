<?php

namespace App\DTO\Update;

final class CategoryUpdateData
{
    /**
     * Capture the category name that should replace the stored value.
     */
    public function __construct(
        public readonly string $name,
    ) {
    }

    /**
     * Convert the category update data into persistence attributes.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
        ];
    }
}
