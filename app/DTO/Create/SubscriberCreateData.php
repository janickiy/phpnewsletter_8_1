<?php

namespace App\DTO\Create;

use Carbon\CarbonInterface;

final class SubscriberCreateData
{
    /**
     * Capture a new subscriber's identity, state, token, and category assignments.
     */
    public function __construct(
        public readonly string $email,
        public readonly string $name,
        public readonly int $active,
        public readonly string $token,
        public readonly CarbonInterface|string $timeSent,
        public readonly array $categoryIds = [],
    ) {
    }

    /**
     * Convert the subscriber creation data into persistence attributes.
     */
    public function toArray(): array
    {
        return [
            'email' => $this->email,
            'name' => $this->name,
            'active' => $this->active,
            'token' => $this->token,
            'timeSent' => $this->timeSent,
        ];
    }
}
