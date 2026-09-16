<?php

namespace App\DTO\Create;

class InstallAdminCreateData
{
    /**
     * Capture the administrator credentials supplied during application installation.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $login,
        public readonly string $role,
        public readonly string $password,
    ) {
    }
}
