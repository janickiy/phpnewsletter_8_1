<?php

namespace Tests\Feature;

use App\Models\Settings;
use App\Services\MailingDelayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class MailingDelayServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_configured_delay_is_applied_once_between_emails(): void
    {
        Settings::query()->create([
            'name' => 'SLEEP',
            'value' => '20',
        ]);

        Sleep::fake();

        try {
            $delay = app(MailingDelayService::class);

            $delay->waitBetween(0);
            $delay->waitBetween(1);

            Sleep::assertSleptTimes(1);
            Sleep::assertSlept(
                fn ($duration) => (int) $duration->totalSeconds === 20
            );
        } finally {
            Sleep::fake(false);
        }
    }
}
