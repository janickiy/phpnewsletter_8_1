<?php

namespace App\DTO\Update;

final class ProjectUpdateData
{
    /**
     * Capture project changes. Null owner or membership lists preserve existing values.
     *
     * @param int[]|null $projectAdminIds An empty list removes all project administrators.
     * @param int[]|null $moderatorIds An empty list removes all moderators.
     */
    public function __construct(
        public readonly string $name,
        public readonly bool $status,
        public readonly ?string $description = null,
        public readonly ?int $ownerId = null,
        public readonly ?array $projectAdminIds = null,
        public readonly ?array $moderatorIds = null,
    ) {
    }

    /**
     * Convert project changes to persistence attributes, omitting an unchanged owner.
     */
    public function toArray(): array
    {
        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
        ];

        if ($this->ownerId !== null) {
            $data['owner_id'] = $this->ownerId;
        }

        return $data;
    }
}
