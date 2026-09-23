<?php

namespace App\DTO\Create;

final class CategoryCreateData
{
    /**
     * Capture the category name required to create a subscriber category.
     */
    public function __construct(
        public readonly string $name,
    ) {
    }

    /**
     * Convert the category creation data into persistence attributes.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
        ];
    }
}
