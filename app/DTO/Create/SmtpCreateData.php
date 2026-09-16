<?php

namespace App\DTO\Create;

final class SmtpCreateData
{
    /**
     * Capture all connection and activation settings for a new SMTP server.
     */
    public function __construct(
        public readonly string  $host,
        public readonly string $username,
        public readonly string $email,
        public readonly ?string $password,
        public readonly int $port,
        public readonly string $authentication,
        public readonly string $secure,
        public readonly int $timeout,
        public readonly int $active = 1,
    ) {
    }

    /**
     * Convert the SMTP creation data into persistence attributes.
     */
    public function toArray(): array
    {
        return [
            'host' => $this->host,
            'username' => $this->username,
            'email' => $this->email,
            'password' => $this->password,
            'port' => $this->port,
            'authentication' => $this->authentication,
            'secure' => $this->secure,
            'timeout' => $this->timeout,
            'active' => $this->active,
        ];
    }
}
