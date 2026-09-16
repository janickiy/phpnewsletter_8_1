<?php

namespace App\DTO\Create;

use Carbon\CarbonInterface;

class RedirectCreateData
{
    /**
     * Capture a tracked redirect URL together with its recipient and timestamp.
     */
    public function __construct(
        public readonly string $url,
        public readonly CarbonInterface|string $time,
        public readonly string $email,
    ) {
    }

    /**
     * Convert the redirect tracking data into persistence attributes.
     */
    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'time' => $this->time,
            'email' => $this->email,
        ];
    }
}
