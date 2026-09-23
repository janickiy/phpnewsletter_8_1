<?php

namespace Tests\Feature;

use App\Helpers\SendEmailHelper;
use App\Models\{Logs, Process, Project, Subscribers, Templates, User};
use App\Repositories\SubscriberRepository;
use App\Services\SendMailService;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery\MockInterface;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Tests\TestCase;

class ManualMailingConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private Templates $template;
    private Subscribers $subscriber;
    private Logs $log;
    private ManualConcurrencyMailer $mailer;
    private ManualConcurrencySendMailService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = User::query()->create([
            'name' => 'Mailing manager', 'login' => 'manual-concurrency-manager',
            'role' => User::ROLE_PROJECT_ADMIN, 'password' => 'password',
        ]);
        $project = Project::query()->create([
            'name' => 'Concurrent mailing project', 'owner_id' => $this->manager->id, 'status' => true,
        ]);
        $this->template = Templates::query()->create([
            'name' => 'Mailing template', 'body' => '<p>Mailing</p>', 'prior' => 0, 'project_id' => $project->id,
        ]);
        $this->subscriber = $this->subscriberFixture([
            'name' => 'Recipient', 'email' => 'concurrency@example.test', 'active' => 1,
            'token' => md5('concurrency@example.test'),
        ], [$project->id]);
        $this->log = Logs::query()->create(['time' => now(), 'user_id' => $this->manager->id]);
        $this->actingAs($this->manager);
        $this->mailer = new ManualConcurrencyMailer();
        $this->bindService();
    }

    public function test_competing_request_is_rejected_before_reading_recipients_or_changing_process(): void
    {
        $this->mock(SubscriberRepository::class, function (MockInterface $mock): void {
            $mock->shouldReceive('getSubscribers')->once()->andReturn(collect([$this->subscriber]));
        });
        $this->bindService();
        $this->mailer->beforeSend = function (): void {
            Process::query()->where('user_id', $this->manager->id)->update(['command' => 'pause']);

            try {
                $this->service->sendOut($this->request());
                $this->fail('An overlapping request for the same batch must be rejected.');
            } catch (HttpExceptionInterface $exception) {
                $this->assertSame(409, $exception->getStatusCode());
                $this->assertSame(__('frontend.msg.mailing_already_running'), $exception->getMessage());
            }

            $this->assertDatabaseHas('process', ['user_id' => $this->manager->id, 'command' => 'pause']);
            $this->assertDatabaseCount('ready_sent', 0);
            $this->assertSame([], $this->mailer->deliveries);
        };

        $result = $this->service->sendOut($this->request());

        $this->assertTrue($result['completed']);
        $this->assertSame([$this->subscriber->email], $this->mailer->deliveries);
        $this->assertDatabaseCount('ready_sent', 1);
    }

    public function test_completed_batch_releases_lock_and_resuming_does_not_repeat_delivery(): void
    {
        foreach ([1, 2] as $attempt) {
            $this->postJson(route('admin.ajax.action'), $this->payload() + ['action' => 'send_out'])
                ->assertOk()->assertJsonPath('completed', true);
            $this->assertSame([$this->subscriber->email], $this->mailer->deliveries);
            $this->assertDatabaseCount('ready_sent', 1);
        }
    }

    public function test_transport_exception_releases_batch_lock_so_it_can_be_resumed(): void
    {
        $this->mailer->beforeSend = static function (): void {
            throw new RuntimeException('Simulated transport failure before delivery.');
        };

        try {
            $this->service->sendOut($this->request());
            $this->fail('The transport failure must propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated transport failure before delivery.', $exception->getMessage());
        }

        $this->assertDatabaseCount('ready_sent', 0);
        $this->assertSame([], $this->mailer->deliveries);
        $this->assertTrue($this->service->sendOut($this->request())['completed']);
        $this->assertSame([$this->subscriber->email], $this->mailer->deliveries);
        $this->assertDatabaseHas('ready_sent', [
            'log_id' => $this->log->id, 'subscriber_id' => $this->subscriber->id, 'success' => 1,
        ]);
    }

    private function bindService(): void
    {
        $this->service = $this->app->make(ManualConcurrencySendMailService::class);
        $this->service->mailer = $this->mailer;
        $this->app->instance(SendMailService::class, $this->service);
    }

    private function request(): Request
    {
        return Request::create('/ajax', 'POST', $this->payload());
    }

    private function payload(): array
    {
        return ['templateId' => [$this->template->id], 'logId' => $this->log->id];
    }
}

class ManualConcurrencyMailer extends SendEmailHelper
{
    public array $deliveries = [];
    public ?Closure $beforeSend = null;

    public function sendEmail(?int $attach = null): array
    {
        $callback = $this->beforeSend;
        $this->beforeSend = null;
        $callback?->__invoke();
        $this->deliveries[] = $this->email;

        return ['result' => true, 'error' => null];
    }
}

class ManualConcurrencySendMailService extends SendMailService
{
    public SendEmailHelper $mailer;

    protected function createSendEmailHelper(): SendEmailHelper
    {
        return $this->mailer;
    }
}
