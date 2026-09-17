<?php

namespace App\DTO\Create;

final class CategoryCreateData
{
    /**
     * Capture the category name required to create a subscriber category.
     */
    public function __construct(
        public readonly string $name,
        public readonly int $projectId,
    ) {
    }

    /**
     * Convert the category creation data into persistence attributes.
     */
    public function toArray(): array
    {
        return [
            'project_id' => $this->projectId,
            'name' => $this->name,
        ];
    }
}
