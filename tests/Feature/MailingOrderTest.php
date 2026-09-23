<?php

namespace Tests\Feature;

use App\Models\Logs;
use App\Models\Schedule;
use App\Models\Settings;
use App\Models\Templates;
use App\Models\User;
use App\Repositories\SubscriberRepository;
use App\Services\SendMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MailingOrderTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('legacyRandomizationValues')]
    public function test_manual_mailing_uses_subscriber_id_order(bool $legacyEnabled): void
    {
        $this->configureMailing($legacyEnabled);

        $admin = User::query()->create([
            'name' => 'Mailing administrator',
            'login' => 'mailing-order-admin',
            'role' => User::ROLE_ADMIN,
            'password' => 'secret123',
        ]);
        $this->actingAs($admin);

        $template = $this->template();
        $log = Logs::query()->create(['time' => now()]);

        $this->mock(SubscriberRepository::class, function (MockInterface $mock) use ($log, $template) {
            $mock->shouldReceive('getSubscribers')
                ->once()
                ->with($log->id, $template->id, [], 'subscribers.id', 20, $this->eligibilityInterval(), true)
                ->andReturn(collect());
        });

        $result = app(SendMailService::class)->sendOut(Request::create('/ajax', 'POST', [
            'templateId' => [$template->id],
            'logId' => $log->id,
        ]));

        $this->assertSame(['result' => true, 'completed' => true], $result);
        $this->assertDatabaseHas('process', ['user_id' => $admin->id, 'command' => 'stop']);
        $this->assertDatabaseCount('ready_sent', 0);
    }

    #[DataProvider('consoleMailings')]
    public function test_console_mailing_uses_subscriber_id_order(
        string $command,
        string $repositoryMethod,
        bool $legacyEnabled,
    ): void {
        $this->configureMailing($legacyEnabled);

        $schedule = Schedule::query()->create([
            'project_id' => $this->testProjectId(),
            'event_name' => 'Mailing order schedule',
            'event_start' => now()->subHour(),
            'event_end' => now()->addHour(),
            'template_id' => $this->template()->id,
        ]);

        $this->mock(SubscriberRepository::class, function (MockInterface $mock) use ($schedule, $repositoryMethod) {
            $mock->shouldReceive($repositoryMethod)
                ->once()
                ->with($schedule->id, 'subscribers.id', 20, $this->eligibilityInterval())
                ->andReturn(collect());
        });

        $this->artisan($command)
            ->expectsOutput('sent: 0')
            ->expectsOutput('no sent: 0')
            ->assertSuccessful();

        $this->assertDatabaseCount('ready_sent', 0);
    }

    public static function legacyRandomizationValues(): array
    {
        return [
            'legacy randomization enabled' => [true],
            'removed setting absent' => [false],
        ];
    }

    public static function consoleMailings(): array
    {
        return [
            'scheduled delivery with legacy randomization' => ['emails:send', 'getSubscribersNotReadySent', true],
            'scheduled delivery without retired setting' => ['emails:send', 'getSubscribersNotReadySent', false],
            'retry with legacy randomization' => ['emails:unsent', 'getSubscribersUnSent', true],
            'retry without retired setting' => ['emails:unsent', 'getSubscribersUnSent', false],
        ];
    }

    private function configureMailing(bool $legacyEnabled): void
    {
        foreach ([
            'LIMIT_SEND' => '1',
            'LIMIT_NUMBER' => '20',
            'INTERVAL_TYPE' => 'hour',
            'INTERVAL_NUMBER' => '2',
        ] as $name => $value) {
            Settings::query()->create(compact('name', 'value'));
        }

        if ($legacyEnabled) {
            Settings::query()->create(['name' => 'RANDOM_SEND', 'value' => '1']);
        }
    }

    private function template(): Templates
    {
        return Templates::query()->create([
            'project_id' => $this->testProjectId(),
            'name' => 'Mailing order template',
            'body' => '<p>Mailing order test</p>',
            'prior' => 0,
        ]);
    }

    private function eligibilityInterval(): string
    {
        return "(subscribers.timeSent IS NULL OR subscribers.timeSent < NOW() - INTERVAL '2' HOUR)";
    }
}
