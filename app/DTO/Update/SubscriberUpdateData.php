<?php

namespace App\DTO\Update;

final class SubscriberUpdateData
{
    /**
     * Capture the subscriber identity values that should be updated.
     */
    public function __construct(
        public readonly string $email,
        public readonly ?string $name,
        public readonly ?array $projectIds = null,
        public readonly ?array $categoryIds = null,
    ) {
    }

    /**
     * Convert the subscriber update data into persistence attributes.
     */
    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'name' => $this->name,
        ];
    }
}
