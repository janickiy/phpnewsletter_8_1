<?php

namespace Tests\Feature;

use App\DTO\Update\ReadySentReadData;
use App\Helpers\SendEmailHelper;
use App\Models\Logs;
use App\Models\Project;
use App\Models\ReadySent;
use App\Models\Schedule;
use App\Models\Settings;
use App\Models\Subscribers;
use App\Models\Templates;
use App\Models\User;
use App\Repositories\ReadySentRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use PHPMailer\PHPMailer\PHPMailer;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TrackingPixelTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('trackingProjects')]
    public function test_pixel_from_outgoing_mime_records_one_open_in_the_mailing_report(bool $useDefaultProject): void
    {
        URL::forceRootUrl('http://newsletter.test');
        foreach ([
            'HOW_TO_SEND' => 'mail',
            'SENDMAIL_PATH' => '',
            'EMAIL' => 'sender@example.test',
            'FROM' => 'Newsletter tests',
            'LIST_OWNER' => '',
            'RETURN_PATH' => '',
            'CONTENT_TYPE' => 'html',
            'ORGANIZATION' => '',
            'REQUEST_REPLY' => '0',
            'PRECEDENCE' => 'bulk',
            'SHOW_UNSUBSCRIBE_LINK' => '0',
            'UNSUBLINK' => '',
            'URL' => 'http://newsletter.test',
            'REMOVE_SUBSCRIBER' => '0',
        ] as $name => $value) {
            Settings::query()->updateOrCreate(['name' => $name], ['value' => $value]);
        }

        $admin = User::query()->create([
            'name' => 'Tracking administrator', 'login' => 'tracking-admin',
            'role' => User::ROLE_ADMIN, 'password' => 'password',
        ]);
        $projectId = $useDefaultProject ? Project::DEFAULT_ID : $this->testProjectId();
        $subscriber = $this->subscriberFixture([
            'name' => 'Reader', 'email' => 'reader@example.test',
            'active' => 1, 'token' => str_repeat('a', 32),
        ], [$projectId]);
        $otherSubscriber = $this->subscriberFixture([
            'name' => 'Other reader', 'email' => 'other-reader@example.test',
            'active' => 1, 'token' => str_repeat('b', 32),
        ], [$projectId]);
        $template = Templates::query()->create([
            'project_id' => $projectId, 'name' => 'Tracked newsletter',
            'body' => '<p>Hello, %NAME%!</p>', 'prior' => 0,
        ]);
        $otherTemplate = Templates::query()->create([
            'project_id' => $projectId, 'name' => 'Other newsletter',
            'body' => '<p>Other message</p>', 'prior' => 0,
        ]);
        $log = Logs::query()->create(['time' => now(), 'user_id' => $admin->id]);
        $delivery = $this->delivery($log, $subscriber, $template);
        $otherRecipientDelivery = $this->delivery($log, $otherSubscriber, $template);
        $otherTemplateDelivery = $this->delivery($log, $subscriber, $otherTemplate);

        $helper = new TrackingMimeOnlySendEmailHelper;
        $helper->subject = $template->name;
        $helper->body = $template->body;
        $helper->email = $subscriber->email;
        $helper->name = $subscriber->name;
        $helper->subscriberId = $subscriber->id;
        $helper->templateId = $template->id;
        $helper->token = $subscriber->token;

        $this->assertSame(['result' => true, 'error' => null], $helper->sendEmail());
        $mime = $helper->mailer->capturedMime;
        $this->assertStringContainsString('Content-Type: text/html; charset=utf-8', $mime);
        $this->assertSame(1, preg_match('/<img\b[^>]*\bsrc="([^"]+)"[^>]*>/', $mime, $pixel));
        $pixelUrl = html_entity_decode($pixel[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $this->assertSame(route('frontend.pic', [
            'subscriber' => $subscriber->id, 'template' => $template->id,
        ]), $pixelUrl);
        $this->assertNull($delivery->fresh()->readMail);

        foreach ([1, 2] as $requestNumber) {
            auth()->logout();
            // Laravel handles this URL in the isolated test application; no HTTP client is used.
            $response = $this->get($pixelUrl)->assertOk()->assertHeader('Content-Type', 'image/gif');
            $this->assertStringStartsWith('GIF8', $response->getContent());
            $this->assertSame(1, $delivery->fresh()->readMail);
            $this->assertNull($otherRecipientDelivery->fresh()->readMail);
            $this->assertNull($otherTemplateDelivery->fresh()->readMail);

            $summary = $this->actingAs($admin)->getJson(route('admin.datatable.logs', [
                'draw' => $requestNumber, 'start' => 0, 'length' => 10,
            ]))->assertOk()->assertJsonCount(1, 'data')->json('data.0');
            $this->assertSame($log->id, (int) $summary['id']);
            $this->assertSame(1, (int) $summary['read_mail']);
        }

        if ($useDefaultProject) {
            $this->assertDatabaseCount('projects', 0);
        }
    }

    public static function trackingProjects(): array
    {
        return [
            'virtual default project' => [true],
            'stored project' => [false],
        ];
    }

    private function delivery(Logs $log, Subscribers $subscriber, Templates $template): ReadySent
    {
        return ReadySent::query()->create([
            'project_id' => $template->project_id,
            'subscriber_id' => $subscriber->id,
            'email' => $subscriber->email,
            'template_id' => $template->id,
            'template' => $template->name,
            'success' => 1,
            'readMail' => null,
            'log_id' => $log->id,
        ]);
    }

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

class TrackingMimeOnlySendEmailHelper extends SendEmailHelper
{
    public readonly TrackingMimeOnlyMailer $mailer;

    public function __construct()
    {
        $this->mailer = new TrackingMimeOnlyMailer;
    }

    protected function createMailer(): PHPMailer
    {
        return $this->mailer;
    }
}

class TrackingMimeOnlyMailer extends PHPMailer
{
    public string $capturedMime = '';

    public function send(): bool
    {
        // Build the actual MIME message without invoking any mail transport.
        if (! $this->preSend()) {
            return false;
        }

        $this->capturedMime = $this->getSentMIMEMessage();

        return true;
    }
}
