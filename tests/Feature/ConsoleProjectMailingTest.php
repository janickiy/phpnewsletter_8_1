<?php

namespace Tests\Feature;

use App\Console\Commands\SendEmails;
use App\Console\Commands\SendUnsentEmails;
use App\Helpers\SendEmailHelper;
use App\Models\{Category, Project, ReadySent, Schedule, ScheduleCategory, Settings, Subscribers, Subscriptions, Templates};
use App\Services\MailingDelayService;
use Illuminate\Console\CacheCommandMutex;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

class ConsoleProjectMailingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance(MailingDelayService::class, new class extends MailingDelayService {
            public function waitBetween(int $completedAttempts): void {}
        });
    }

    #[DataProvider('commandAudiences')]
    public function test_console_commands_use_the_correct_project_audience_without_an_authenticated_user(string $mode, string $audience): void
    {
        $namedProjectId = $this->testProjectId();
        $projectId = $audience === 'default' ? Project::DEFAULT_ID : $namedProjectId;
        $foreignProjectId = $audience === 'default' ? $namedProjectId : Project::DEFAULT_ID;
        $schedule = $this->schedule($projectId);
        $category = $this->category($projectId, 'Selected');
        $anotherCategory = $this->category($projectId, 'Another');
        if ($audience !== 'named without categories') {
            ScheduleCategory::query()->create(['schedule_id' => $schedule->id, 'category_id' => $category->id]);
        }
        $selected = $this->subscriber($projectId, 'selected');
        $selected->projects()->attach($foreignProjectId);
        $uncategorized = $this->subscriber($projectId, 'uncategorized');
        $otherCategoryMember = $this->subscriber($projectId, 'other-category');
        $inactive = $this->subscriber($projectId, 'inactive', ['active' => 0]);
        $foreign = $this->subscriber($foreignProjectId, 'foreign');
        $alreadySent = $this->subscriber($projectId, 'already-sent');
        foreach ([$selected, $inactive, $foreign, $alreadySent] as $subscriber) {
            $this->subscribe($subscriber, $category);
        }
        $this->subscribe($selected, $anotherCategory);
        $this->subscribe($otherCategoryMember, $anotherCategory);
        $this->recordAttempt($schedule, $alreadySent, true);
        if ($mode === 'retry') {
            foreach ([$selected, $uncategorized, $otherCategoryMember, $inactive, $foreign] as $subscriber) {
                $this->recordAttempt($schedule, $subscriber, false);
            }
            // Legacy duplicate failure records must cause just one actual delivery.
            $this->recordAttempt($schedule, $selected, false);
            // A stale failure must not resend a recipient who already succeeded.
            $this->recordAttempt($schedule, $alreadySent, false);
        }
        $mailer = new ConsoleProjectRecordingMailer();
        $expected = $audience === 'default' ? [$selected] : [$selected, $uncategorized, $otherCategoryMember];

        $output = $this->runCommand($mode, $mailer);

        $this->assertSame(array_map(fn ($subscriber) => $subscriber->email, $expected), array_column($mailer->deliveries, 'email'));
        $this->assertMatchesRegularExpression('/^sent: '.count($expected).'$/m', $output);
        $this->assertMatchesRegularExpression('/^no sent: 0$/m', $output);
        foreach ($expected as $subscriber) {
            $this->assertDatabaseHas('ready_sent', [
                'schedule_id' => $schedule->id, 'project_id' => $projectId, 'subscriber_id' => $subscriber->id,
                'template_id' => $schedule->template_id, 'success' => 1, 'errorMsg' => null,
            ]);
            $this->assertNotNull($subscriber->fresh()->timeSent);
        }
        foreach ([$inactive, $foreign, $alreadySent] as $subscriber) {
            $this->assertNull($subscriber->fresh()->timeSent);
        }
        if ($audience === 'default') {
            $this->assertNull($uncategorized->fresh()->timeSent);
            $this->assertNull($otherCategoryMember->fresh()->timeSent);
        }
        $this->assertSame([$schedule->template_id], array_values(array_unique(array_column($mailer->deliveries, 'template_id'))));
        $this->assertSame([$schedule->template_id], array_values(array_unique(array_column($mailer->deliveries, 'attachment_template_id'))));
        $firstRunCount = ReadySent::query()->count();
        $mailer->deliveries = [];

        $output = $this->runCommand($mode, $mailer);

        $this->assertSame([], $mailer->deliveries);
        $this->assertMatchesRegularExpression('/^sent: 0$/m', $output);
        $this->assertDatabaseCount('ready_sent', $firstRunCount);
    }

    #[DataProvider('commandModes')]
    public function test_legacy_default_schedule_without_categories_never_sends_to_the_entire_project(string $mode): void
    {
        $schedule = $this->schedule(Project::DEFAULT_ID);
        $subscriber = $this->subscriber(Project::DEFAULT_ID, 'default');
        $this->subscribe($subscriber, $this->category(Project::DEFAULT_ID, 'Default category'));
        if ($mode === 'retry') {
            $this->recordAttempt($schedule, $subscriber, false);
        }
        $mailer = new ConsoleProjectRecordingMailer();

        $output = $this->runCommand($mode, $mailer);

        $this->assertSame([], $mailer->deliveries);
        $this->assertMatchesRegularExpression('/^sent: 0$/m', $output);
        $this->assertDatabaseMissing('ready_sent', ['schedule_id' => $schedule->id, 'success' => 1]);
        $this->assertNull($subscriber->fresh()->timeSent);
    }

    #[DataProvider('commandModes')]
    public function test_commands_skip_future_expired_inactive_and_mismatched_schedules(string $mode): void
    {
        $projectId = $this->testProjectId();
        $subscriber = $this->subscriber($projectId, 'project-member');
        $due = $this->schedule($projectId);
        $future = $this->schedule($projectId, ['event_start' => now()->addHour(), 'event_end' => now()->addHours(2)]);
        $expired = $this->schedule($projectId, ['event_start' => now()->subHours(2), 'event_end' => now()->subHour()]);
        $mismatched = $this->schedule($projectId, ['template_id' => $this->template(Project::DEFAULT_ID)->id]);
        $inactiveProject = Project::query()->create([
            'name' => 'Inactive project', 'owner_id' => Project::query()->findOrFail($projectId)->owner_id, 'status' => 0,
        ]);
        $inactiveSchedule = $this->schedule($inactiveProject->id);
        $inactiveSubscriber = $this->subscriber($inactiveProject->id, 'inactive-project-member');
        if ($mode === 'retry') {
            foreach ([$due, $future, $expired, $mismatched] as $schedule) {
                $this->recordAttempt($schedule, $subscriber, false);
            }
            $this->recordAttempt($inactiveSchedule, $inactiveSubscriber, false);
        }
        $mailer = new ConsoleProjectRecordingMailer();

        $output = $this->runCommand($mode, $mailer);

        $this->assertSame([$subscriber->email], array_column($mailer->deliveries, 'email'));
        $this->assertSame([$due->template_id], array_column($mailer->deliveries, 'template_id'));
        $this->assertMatchesRegularExpression('/^sent: 1$/m', $output);
        foreach ([$future, $expired, $mismatched, $inactiveSchedule] as $schedule) {
            $this->assertDatabaseMissing('ready_sent', ['schedule_id' => $schedule->id, 'success' => 1]);
        }
        $this->assertNull($inactiveSubscriber->fresh()->timeSent);
    }

    public function test_send_then_retry_records_successes_failures_and_does_not_repeat_completed_deliveries(): void
    {
        $projectId = $this->testProjectId();
        $schedule = $this->schedule($projectId);
        $success = $this->subscriber($projectId, 'success');
        $recoverable = $this->subscriber($projectId, 'recoverable');
        $failure = $this->subscriber($projectId, 'failure');
        $mailer = new ConsoleProjectRecordingMailer();
        $mailer->failingEmails = [$recoverable->email, $failure->email];

        $output = $this->runCommand('send', $mailer);

        $this->assertSame([$success->email, $recoverable->email, $failure->email], array_column($mailer->deliveries, 'email'));
        $this->assertMatchesRegularExpression('/^sent: 1$/m', $output);
        $this->assertMatchesRegularExpression('/^no sent: 2$/m', $output);
        $this->assertDatabaseCount('ready_sent', 3);
        $this->assertNotNull($success->fresh()->timeSent);
        $this->assertNull($recoverable->fresh()->timeSent);
        $this->assertNull($failure->fresh()->timeSent);
        $mailer->deliveries = [];
        $this->runCommand('send', $mailer);
        $this->assertSame([], $mailer->deliveries);

        $mailer->failingEmails = [$failure->email];
        $output = $this->runCommand('retry', $mailer);

        $this->assertSame([$recoverable->email, $failure->email], array_column($mailer->deliveries, 'email'));
        $this->assertMatchesRegularExpression('/^sent: 1$/m', $output);
        $this->assertMatchesRegularExpression('/^no sent: 1$/m', $output);
        $this->assertDatabaseHas('ready_sent', ['schedule_id' => $schedule->id, 'subscriber_id' => $recoverable->id, 'success' => 1, 'errorMsg' => null]);
        $this->assertDatabaseHas('ready_sent', ['schedule_id' => $schedule->id, 'subscriber_id' => $failure->id, 'success' => 0, 'errorMsg' => 'Simulated delivery failure']);
        $this->assertNotNull($recoverable->fresh()->timeSent);
        $this->assertNull($failure->fresh()->timeSent);
        $this->assertDatabaseCount('ready_sent', 3);
        $mailer->deliveries = [];

        $this->runCommand('retry', $mailer);

        $this->assertSame([$failure->email], array_column($mailer->deliveries, 'email'));
        $this->assertDatabaseCount('ready_sent', 3);
    }

    public function test_retry_success_and_failure_histories_are_isolated_by_schedule(): void
    {
        $projectId = $this->testProjectId();
        $subscriber = $this->subscriber($projectId, 'shared-recipient');
        $completed = $this->schedule($projectId);
        $pending = $this->schedule($projectId);
        $expired = $this->schedule($projectId, ['event_start' => now()->subHours(2), 'event_end' => now()->subHour()]);
        $this->recordAttempt($completed, $subscriber, true);
        $this->recordAttempt($completed, $subscriber, false);
        $this->recordAttempt($pending, $subscriber, false);
        $this->recordAttempt($expired, $subscriber, false);
        $mailer = new ConsoleProjectRecordingMailer();

        $this->runCommand('retry', $mailer);

        $this->assertSame([$subscriber->email], array_column($mailer->deliveries, 'email'));
        $this->assertSame([$pending->template_id], array_column($mailer->deliveries, 'template_id'));
        $this->assertDatabaseHas('ready_sent', ['schedule_id' => $pending->id, 'subscriber_id' => $subscriber->id, 'success' => 1, 'errorMsg' => null]);
        $this->assertDatabaseHas('ready_sent', ['schedule_id' => $completed->id, 'subscriber_id' => $subscriber->id, 'success' => 0]);
        $this->assertDatabaseHas('ready_sent', ['schedule_id' => $expired->id, 'subscriber_id' => $subscriber->id, 'success' => 0]);
        $this->assertDatabaseCount('ready_sent', 4);
    }

    #[DataProvider('commandModes')]
    public function test_project_wide_mailings_respect_delivery_intervals_and_limits(string $mode): void
    {
        foreach (['LIMIT_SEND' => '1', 'LIMIT_NUMBER' => '1', 'INTERVAL_TYPE' => 'hour', 'INTERVAL_NUMBER' => '2'] as $name => $value) {
            Settings::query()->create(compact('name', 'value'));
        }
        $projectId = $this->testProjectId();
        $schedule = $this->schedule($projectId);
        $recent = $this->subscriber($projectId, 'recent', ['timeSent' => now()]);
        $eligible = $this->subscriber($projectId, 'eligible', ['timeSent' => now()->subDays(2)]);
        $next = $this->subscriber($projectId, 'next');
        if ($mode === 'retry') {
            foreach ([$recent, $eligible, $next] as $subscriber) {
                $this->recordAttempt($schedule, $subscriber, false);
            }
        }
        $mailer = new ConsoleProjectRecordingMailer();

        $this->runCommand($mode, $mailer);

        $this->assertSame([$eligible->email], array_column($mailer->deliveries, 'email'));
        $this->assertDatabaseMissing('ready_sent', ['subscriber_id' => $recent->id, 'success' => 1]);
        $this->assertNull($next->fresh()->timeSent);
        $mailer->deliveries = [];

        $this->runCommand($mode, $mailer);

        $this->assertSame([$next->email], array_column($mailer->deliveries, 'email'));
        $this->assertDatabaseMissing('ready_sent', ['subscriber_id' => $recent->id, 'success' => 1]);
    }

    #[DataProvider('commandModes')]
    public function test_plain_command_invocations_do_not_overlap_an_existing_run(string $mode): void
    {
        $command = $this->app->make($mode === 'send' ? SendEmails::class : SendUnsentEmails::class);
        $command->setLaravel($this->app);
        $mutex = $this->app->make(CacheCommandMutex::class);
        $this->assertTrue($mutex->create($command));

        try {
            $this->travel(2)->hours();
            $tester = new CommandTester($command);
            $this->assertSame(0, $tester->execute([]));
            $this->assertStringContainsString('command is already running', $tester->getDisplay());
            $this->assertDatabaseCount('logs', 0);
            $this->assertDatabaseCount('ready_sent', 0);
        } finally {
            $mutex->forget($command);
        }

        $tester = new CommandTester($command);
        $this->assertSame(0, $tester->execute([]));
        $this->assertMatchesRegularExpression('/^sent: 0$/m', $tester->getDisplay());
    }

    public function test_interrupted_retry_does_not_repeat_deliveries_completed_before_the_interruption(): void
    {
        $projectId = $this->testProjectId();
        $schedule = $this->schedule($projectId);
        $first = $this->subscriber($projectId, 'first');
        $second = $this->subscriber($projectId, 'second');
        $this->recordAttempt($schedule, $first, false);
        $this->recordAttempt($schedule, $second, false);
        $mailer = new ConsoleProjectRecordingMailer();
        $mailer->throwOnEmail = $second->email;

        try {
            $this->runCommand('retry', $mailer);
            $this->fail('The second delivery should interrupt the batch.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated interruption', $exception->getMessage());
        }

        $this->assertDatabaseHas('ready_sent', ['schedule_id' => $schedule->id, 'subscriber_id' => $first->id, 'success' => 1]);
        $this->assertNotNull($first->fresh()->timeSent);
        $this->assertDatabaseHas('ready_sent', ['schedule_id' => $schedule->id, 'subscriber_id' => $second->id, 'success' => 0]);
        $mailer->throwOnEmail = null;
        $mailer->deliveries = [];

        $this->runCommand('retry', $mailer);

        $this->assertSame([$second->email], array_column($mailer->deliveries, 'email'));
        $this->assertDatabaseCount('ready_sent', 2);
        $this->assertDatabaseHas('ready_sent', ['schedule_id' => $schedule->id, 'subscriber_id' => $second->id, 'success' => 1]);
    }

    public static function commandModes(): array
    {
        return ['emails:send' => ['send'], 'emails:unsent' => ['retry']];
    }

    public static function commandAudiences(): array
    {
        $cases = [];
        foreach (['send', 'retry'] as $mode) {
            foreach (['default', 'named without categories', 'named with legacy categories'] as $audience) {
                $cases[$mode.', '.$audience] = [$mode, $audience];
            }
        }

        return $cases;
    }

    private function runCommand(string $mode, ConsoleProjectRecordingMailer $mailer): string
    {
        $this->app['auth']->forgetGuards();
        $this->assertGuest();
        $command = $this->app->make($mode === 'retry' ? ConsoleProjectSendUnsentEmails::class : ConsoleProjectSendEmails::class);
        $command->mailer = $mailer;
        $command->setLaravel($this->app);
        $tester = new CommandTester($command);
        $this->assertSame(0, $tester->execute([]));

        return $tester->getDisplay();
    }

    private function template(int $projectId): Templates
    {
        return Templates::query()->create([
            'project_id' => $projectId, 'name' => 'Template '.$projectId, 'body' => '<p>Console mailing</p>', 'prior' => 0,
        ]);
    }

    private function schedule(int $projectId, array $attributes = []): Schedule
    {
        return Schedule::query()->create($attributes + [
            'project_id' => $projectId, 'event_name' => 'Console schedule',
            'event_start' => now()->subHour(), 'event_end' => now()->addHour(),
            'template_id' => $this->template($projectId)->id,
        ]);
    }

    private function category(int $projectId, string $name): Category
    {
        return Category::query()->create(['project_id' => $projectId, 'name' => $name]);
    }

    private function subscriber(int $projectId, string $name, array $attributes = []): Subscribers
    {
        return $this->subscriberFixture($attributes + [
            'name' => $name, 'email' => $name.'@example.test', 'token' => md5($name), 'active' => 1,
        ], [$projectId]);
    }

    private function subscribe(Subscribers $subscriber, Category $category): void
    {
        Subscriptions::query()->create(['subscriber_id' => $subscriber->id, 'category_id' => $category->id]);
    }

    private function recordAttempt(Schedule $schedule, Subscribers $subscriber, bool $success): void
    {
        ReadySent::query()->create([
            'schedule_id' => $schedule->id, 'project_id' => $schedule->project_id,
            'subscriber_id' => $subscriber->id, 'email' => $subscriber->email,
            'template_id' => $schedule->template_id, 'template' => $schedule->event_name,
            'success' => $success ? 1 : 0, 'errorMsg' => $success ? null : 'Simulated delivery failure',
        ]);
    }
}

class ConsoleProjectRecordingMailer extends SendEmailHelper
{
    public array $deliveries = [];
    public array $failingEmails = [];
    public ?string $throwOnEmail = null;

    public function sendEmail(?int $attach = null): array
    {
        if ($this->email === $this->throwOnEmail) {
            throw new \RuntimeException('Simulated interruption');
        }

        $this->deliveries[] = [
            'email' => $this->email, 'template_id' => $this->templateId, 'attachment_template_id' => $attach,
        ];
        $success = !in_array($this->email, $this->failingEmails, true);

        return ['result' => $success, 'error' => $success ? null : 'Simulated delivery failure'];
    }
}

class ConsoleProjectSendEmails extends SendEmails
{
    public SendEmailHelper $mailer;

    protected function createSendEmailHelper(): SendEmailHelper
    {
        return $this->mailer;
    }
}

class ConsoleProjectSendUnsentEmails extends SendUnsentEmails
{
    public SendEmailHelper $mailer;

    protected function createSendEmailHelper(): SendEmailHelper
    {
        return $this->mailer;
    }
}
