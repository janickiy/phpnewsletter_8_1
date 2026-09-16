<?php

namespace App\DTO\Create;

class TemplatesCreateData
{
    /**
     * Capture the subject, content, and priority of a new email template.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $body,
        public readonly int $prior,
    ) {
    }

    /**
     * Convert the template creation data into persistence attributes.
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
