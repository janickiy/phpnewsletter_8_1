<?php

namespace App\DTO\Create;

final class UserCreateData
{
    /**
     * Capture the profile, role, and credentials required to create an administrator.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $login,
        public readonly string $role,
        public readonly string $password,
        public readonly ?string $description = null,
    ) {
    }

    /**
     * Convert the user creation data into persistence attributes.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'login' => $this->login,
            'role' => $this->role,
            'password' => $this->password,
            'description' => $this->description,
        ];
    }
}
