<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case ProjectAdmin = 'project_admin';
    case Moderator = 'moderator';

    public function label(): string
    {
        return __('frontend.str.projects.roles.' . $this->value);
    }

    public function description(): string
    {
        return __('frontend.str.projects.role_descriptions.' . $this->value);
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Admin => 'text-bg-danger',
            self::ProjectAdmin => 'text-bg-primary',
            self::Moderator => 'text-bg-success',
        };
    }

    public function isSuperAdmin(): bool
    {
        return $this === self::Admin;
    }

    public static function labelFor(?string $value): string
    {
        return self::tryFrom($value ?? '')?->label() ?? $value ?? '';
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $role) {
            $options[$role->value] = $role->label();
        }

        return $options;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** @return array<string, string> */
    public static function descriptions(): array
    {
        $descriptions = [];

        foreach (self::cases() as $role) {
            $descriptions[$role->value] = $role->description();
        }

        return $descriptions;
    }
}
