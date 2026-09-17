<?php

namespace App\DTO\Create;

final class ProjectCreateData
{
    /**
     * Capture a new project's attributes and assigned users.
     *
     * @param int[] $projectAdminIds
     * @param int[] $moderatorIds
     */
    public function __construct(
        public readonly string $name,
        public readonly bool $status,
        public readonly int $ownerId,
        public readonly ?string $description = null,
        public readonly array $projectAdminIds = [],
        public readonly array $moderatorIds = [],
    ) {
    }

    /**
     * Convert project attributes to persistence data; memberships are saved separately.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'owner_id' => $this->ownerId,
        ];
    }
}
