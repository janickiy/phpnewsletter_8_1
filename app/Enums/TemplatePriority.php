<?php

namespace App\Enums;

enum TemplatePriority: int
{
    case Normal = 0;
    case High = 1;
    case Low = 2;

    public function label(): string
    {
        return __('frontend.str.'.strtolower($this->name));
    }

    public function formLabel(): string
    {
        return __('frontend.form.'.strtolower($this->name));
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Normal => 'text-bg-primary',
            self::High => 'text-bg-danger',
            self::Low => 'text-bg-secondary',
        };
    }

    public function mailPriority(): int
    {
        return match ($this) {
            self::Normal => 3,
            self::High => 1,
            self::Low => 5,
        };
    }

    public static function values(): array
    {
        return array_map(static fn (self $priority): int => $priority->value, self::cases());
    }

    public static function options(): array
    {
        $options = [];

        foreach ([self::Normal, self::Low, self::High] as $priority) {
            $options[$priority->value] = $priority->formLabel();
        }

        return $options;
    }
}
