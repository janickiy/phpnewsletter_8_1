<?php

namespace App\DTO\Update;

final class TemplatesUpdateData
{
    /**
     * Capture the template subject, content, and priority that should be updated.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $body,
        public readonly int $prior,
    ) {
    }

    /**
     * Convert the template update data into persistence attributes.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'body' => $this->body,
            'prior' => $this->prior,
        ];
    }
}
