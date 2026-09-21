<?php

namespace Tests\Unit;

use App\Enums\TemplatePriority;
use App\Models\Templates;
use Tests\TestCase;

class TemplatePriorityTest extends TestCase
{
    public function test_persisted_priority_codes_keep_their_labels_badge_colors_and_mail_headers(): void
    {
        $expected = [
            [0, TemplatePriority::Normal, 'normal', 'text-bg-primary', 3],
            [1, TemplatePriority::High, 'high', 'text-bg-danger', 1],
            [2, TemplatePriority::Low, 'low', 'text-bg-secondary', 5],
        ];

        foreach (config('app.locales') as $locale) {
            app()->setLocale($locale);

            foreach ($expected as [$value, $priority, $key, $badgeClass, $mailPriority]) {
                $template = new Templates(['prior' => $value]);
                $this->assertSame($value, $priority->value);
                $this->assertSame($priority, $template->getPriority());
                $this->assertSame(__('frontend.str.'.$key), $priority->label());
                $this->assertNotSame('frontend.str.'.$key, $priority->label());
                $this->assertSame($priority->label(), $template->getPrior());
                $this->assertSame($badgeClass, $priority->badgeClass());
                $this->assertSame($mailPriority, $priority->mailPriority());
            }

            $this->assertSame([
                0 => __('frontend.form.normal'),
                2 => __('frontend.form.low'),
                1 => __('frontend.form.high'),
            ], TemplatePriority::options());
        }

        $this->assertEqualsCanonicalizing([0, 1, 2], TemplatePriority::values());
    }

    public function test_unknown_legacy_priority_falls_back_to_normal_without_changing_stored_value(): void
    {
        $template = new Templates(['prior' => 99]);

        $this->assertSame(TemplatePriority::Normal, $template->getPriority());
        $this->assertSame(__('frontend.str.normal'), $template->getPrior());
        $this->assertSame(99, $template->prior);
    }
}
