<?php

namespace App\DTO\Update;

final class UserUpdateData
{
    /**
     * Capture the profile, role, and optional password changes for an administrator.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $login,
        public readonly string $role,
        public readonly ?string $description = null,
        public readonly ?string $password = null,
    ) {
    }

    /**
     * Convert the user update data into persistence attributes.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'login' => $this->login,
            'role' => $this->role,
            'description' => $this->description,
            'password' => $this->password,
        ];
    }
}
