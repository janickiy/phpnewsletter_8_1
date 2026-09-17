<?php

namespace Tests\Feature;

use App\DTO\Update\ReadySentReadData;
use App\Models\Logs;
use App\Models\ReadySent;
use App\Models\Schedule;
use App\Models\Subscribers;
use App\Models\Templates;
use App\Repositories\ReadySentRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackingPixelTest extends TestCase
{
    use RefreshDatabase;

    public function test_mark_as_read_updates_all_matching_deliveries_for_subscriber_and_template(): void
    {
        $subscriber = $this->subscriberFixture([
            'name' => 'Reader',
            'email' => 'reader@example.com',
            'active' => 1,
            'token' => str_repeat('a', 32),
            'timeSent' => now(),
        ], [$this->testProjectId()]);

        $template = Templates::query()->create([
            'project_id' => $this->testProjectId(),
            'name' => 'April newsletter',
            'body' => '<p>Hello</p>',
            'prior' => 0,
        ]);

        $firstLog = Logs::query()->create(['time' => now()]);
        $secondLog = Logs::query()->create(['time' => now()->addMinute()]);
        $schedule = Schedule::query()->create([
            'project_id' => $this->testProjectId(),
            'event_name' => 'April send',
            'event_start' => now()->subMinute(),
            'event_end' => now()->addMinute(),
            'template_id' => $template->id,
        ]);

        $firstDelivery = ReadySent::query()->create([
            'project_id' => $this->testProjectId(),
            'subscriber_id' => $subscriber->id,
            'email' => $subscriber->email,
            'template_id' => $template->id,
            'template' => $template->name,
            'success' => 1,
            'errorMsg' => null,
            'readMail' => null,
            'schedule_id' => $schedule->id,
            'log_id' => $firstLog->id,
        ]);

        $secondDelivery = ReadySent::query()->create([
            'project_id' => $this->testProjectId(),
            'subscriber_id' => $subscriber->id,
            'email' => $subscriber->email,
            'template_id' => $template->id,
            'template' => $template->name,
            'success' => 1,
            'errorMsg' => null,
            'readMail' => null,
            'schedule_id' => $schedule->id,
            'log_id' => $secondLog->id,
        ]);

        app(ReadySentRepository::class)->markAsRead(new ReadySentReadData(
            subscriberId: $subscriber->id,
            templateId: $template->id,
        ));

        $this->assertSame(1, $firstDelivery->fresh()->readMail);
        $this->assertSame(1, $secondDelivery->fresh()->readMail);
    }
}
