<?php

namespace Tests\Feature;

use App\Console\Commands\SendEmails;
use App\Console\Commands\SendUnsentEmails;
use App\Helpers\SendEmailHelper;
use App\Models\Category;
use App\Models\Logs;
use App\Models\Project;
use App\Models\ReadySent;
use App\Models\Schedule;
use App\Models\ScheduleCategory;
use App\Models\Subscribers;
use App\Models\Subscriptions;
use App\Models\Templates;
use App\Models\User;
use App\Services\MailingDelayService;
use App\Services\SendMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

class ProjectDeactivationDuringMailingTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('mailingModes')]
    public function test_deactivation_during_the_delay_stops_that_project_and_preserves_other_deliveries(string $mode): void
    {
        $admin = User::query()->create([
            'name' => 'Administrator', 'login' => 'deactivation-admin', 'role' => User::ROLE_ADMIN, 'password' => 'password',
        ]);
        $this->actingAs($admin);
        $project = Project::query()->create(['name' => 'Paused project', 'owner_id' => $admin->id, 'status' => true]);
        $template = $this->template($project->id);
        $defaultTemplate = $this->template(Project::DEFAULT_ID);
        $category = Category::query()->create(['name' => 'Project readers', 'project_id' => $project->id]);
        $defaultCategory = Category::query()->create(['name' => 'Default readers', 'project_id' => Project::DEFAULT_ID]);
        $sentBeforePause = $this->recipient($project->id, $category, 'sent@example.test');
        $skippedAfterPause = $this->recipient($project->id, $category, 'skipped@example.test');
        $defaultRecipient = $this->recipient(Project::DEFAULT_ID, $defaultCategory, 'default@example.test');

        $this->app->instance(MailingDelayService::class, new class($project->id) extends MailingDelayService {
            public function __construct(private int $projectId) {}

            public function waitBetween(int $completedAttempts): void
            {
                if ($completedAttempts === 1) {
                    Project::query()->whereKey($this->projectId)->update(['status' => false]);
                }
            }
        });
        $mailer = new DeactivationRecordingMailer();

        if ($mode === 'manual') {
            $log = Logs::query()->create(['time' => now(), 'user_id' => $admin->id]);
            $service = $this->app->make(DeactivationSendMailService::class);
            $service->mailer = $mailer;
            $result = $service->sendOut(Request::create('/ajax', 'POST', [
                'templateId' => [$template->id, $defaultTemplate->id],
                'categoryId' => [$defaultCategory->id],
                'logId' => $log->id,
            ]));
            $this->assertTrue($result['completed']);
            $this->assertDatabaseHas('process', ['user_id' => $admin->id, 'command' => 'stop']);
        } else {
            $schedule = $this->schedule($template, $category);
            $defaultSchedule = $this->schedule($defaultTemplate, $defaultCategory);
            if ($mode === 'retry') {
                $this->failedDelivery($schedule, $sentBeforePause);
                $this->failedDelivery($schedule, $skippedAfterPause);
                $this->failedDelivery($defaultSchedule, $defaultRecipient);
            }

            $this->app['auth']->forgetGuards();
            $command = $this->app->make($mode === 'retry' ? DeactivationSendUnsentEmails::class : DeactivationSendEmails::class);
            $command->mailer = $mailer;
            $command->setLaravel($this->app);
            $tester = new CommandTester($command);
            $this->assertSame(0, $tester->execute([]));
            $this->assertStringContainsString('sent: 2', $tester->getDisplay());
            $this->assertStringContainsString('no sent: 0', $tester->getDisplay());
        }

        $this->assertSame(['sent@example.test', 'default@example.test'], $mailer->recipients);
        $this->assertFalse($project->fresh()->status);
        $this->assertDatabaseHas('ready_sent', ['subscriber_id' => $sentBeforePause->id, 'success' => 1]);
        $this->assertDatabaseHas('ready_sent', ['subscriber_id' => $defaultRecipient->id, 'project_id' => Project::DEFAULT_ID, 'success' => 1]);
        $this->assertDatabaseMissing('ready_sent', ['subscriber_id' => $skippedAfterPause->id, 'success' => 1]);
        $this->assertNotNull($sentBeforePause->fresh()->timeSent);
        $this->assertNotNull($defaultRecipient->fresh()->timeSent);
        $this->assertNull($skippedAfterPause->fresh()->timeSent);

        if ($mode === 'retry') {
            $this->assertDatabaseHas('ready_sent', ['subscriber_id' => $skippedAfterPause->id, 'success' => 0]);
        } else {
            $this->assertDatabaseCount('ready_sent', 2);
        }
    }

    public static function mailingModes(): array
    {
        return ['manual' => ['manual'], 'scheduled' => ['scheduled'], 'retry' => ['retry']];
    }

    private function template(int $projectId): Templates
    {
        return Templates::query()->create(['name' => 'Template '.$projectId, 'body' => '<p>Mailing</p>', 'prior' => 0, 'project_id' => $projectId]);
    }

    private function recipient(int $projectId, Category $category, string $email): Subscribers
    {
        $subscriber = $this->subscriberFixture(['name' => $email, 'email' => $email, 'token' => md5($email), 'active' => 1], [$projectId]);
        Subscriptions::query()->create(['subscriber_id' => $subscriber->id, 'category_id' => $category->id]);

        return $subscriber;
    }

    private function schedule(Templates $template, Category $category): Schedule
    {
        $schedule = Schedule::query()->create([
            'event_name' => $template->name, 'event_start' => now()->subHour(), 'event_end' => now()->addHour(),
            'template_id' => $template->id, 'project_id' => $template->project_id,
        ]);
        ScheduleCategory::query()->create(['schedule_id' => $schedule->id, 'category_id' => $category->id]);

        return $schedule;
    }

    private function failedDelivery(Schedule $schedule, Subscribers $subscriber): void
    {
        ReadySent::query()->create([
            'project_id' => $schedule->project_id, 'subscriber_id' => $subscriber->id, 'email' => $subscriber->email,
            'template_id' => $schedule->template_id, 'template' => $schedule->event_name,
            'schedule_id' => $schedule->id, 'success' => 0, 'errorMsg' => 'Test failure',
        ]);
    }
}

class DeactivationRecordingMailer extends SendEmailHelper
{
    public array $recipients = [];

    public function sendEmail(?int $attach = null): array
    {
        $this->recipients[] = $this->email;

        return ['result' => true, 'error' => null];
    }
}

class DeactivationSendMailService extends SendMailService
{
    public SendEmailHelper $mailer;

    protected function createSendEmailHelper(): SendEmailHelper
    {
        return $this->mailer;
    }
}

class DeactivationSendEmails extends SendEmails
{
    public SendEmailHelper $mailer;

    protected function createSendEmailHelper(): SendEmailHelper
    {
        return $this->mailer;
    }
}

class DeactivationSendUnsentEmails extends SendUnsentEmails
{
    public SendEmailHelper $mailer;

    protected function createSendEmailHelper(): SendEmailHelper
    {
        return $this->mailer;
    }
}
