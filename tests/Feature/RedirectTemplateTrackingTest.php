<?php

namespace Tests\Feature;

use App\Helpers\SendEmailHelper;
use App\Models\Project;
use App\Models\Redirect;
use App\Models\Settings;
use App\Models\Subscribers;
use App\Models\Templates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use PHPMailer\PHPMailer\PHPMailer;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RedirectTemplateTrackingTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('projects')]
    public function test_referral_from_outgoing_mime_records_template_identity_and_preserves_its_snapshot(bool $useDefaultProject): void
    {
        $this->configureMail();
        $projectId = $useDefaultProject ? Project::DEFAULT_ID : $this->testProjectId();
        $subscriber = $this->reader([$projectId]);
        $destination = 'https://example.test/news?edition=september&language=ru';
        $template = $this->newsletter($projectId, 'Сентябрьская рассылка', '<a href="%REFERRAL:'.$destination.'%">Read newsletter</a>');

        $helper = new RedirectMimeOnlySendEmailHelper;
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
        $this->assertSame(1, preg_match('/<a\b[^>]*\bhref="([^"]+)"/', $mime, $link));
        $trackingUrl = html_entity_decode($link[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        parse_str((string) parse_url($trackingUrl, PHP_URL_QUERY), $query);
        $this->assertSame((string) $template->id, $query['template_id'] ?? null);
        $this->assertArrayNotHasKey('project_id', $query);

        // Dispatch the generated URL locally; no email transport or external HTTP request is used.
        $this->get($trackingUrl)->assertRedirect($destination);
        $this->assertDatabaseCount('redirect', 1);
        $snapshot = [
            'template_id' => $template->id,
            'template' => 'Сентябрьская рассылка',
            'url' => $destination,
            'email' => $subscriber->email,
        ];
        $this->assertDatabaseHas('redirect', $snapshot);
        $redirectId = Redirect::query()->sole()->id;

        $template->update(['name' => 'Renamed newsletter']);
        $this->assertDatabaseHas('redirect', ['id' => $redirectId] + $snapshot);
        $template->delete();
        $this->assertDatabaseMissing('templates', ['id' => $template->id]);
        $this->assertDatabaseHas('redirect', ['id' => $redirectId] + $snapshot);
    }

    #[DataProvider('legacyProjects')]
    public function test_legacy_referral_without_template_parameter_remains_usable(bool $useDefaultProject, bool $includeProject): void
    {
        $projectId = $useDefaultProject ? Project::DEFAULT_ID : $this->testProjectId();
        $subscriber = $this->reader([$projectId]);
        $destination = 'https://example.test/legacy';
        $parameters = $includeProject ? ['project_id' => $projectId] : [];

        $this->get($this->trackingUrl($subscriber, $destination, $parameters))->assertRedirect($destination);

        $this->assertDatabaseCount('redirect', 1);
        $this->assertDatabaseHas('redirect', [
            'template_id' => null, 'template' => null,
            'url' => $destination, 'email' => $subscriber->email,
        ]);
    }

    public function test_template_resolves_the_project_for_a_subscriber_with_multiple_memberships(): void
    {
        $projectId = $this->testProjectId();
        $subscriber = $this->reader([Project::DEFAULT_ID, $projectId]);
        $template = $this->newsletter($projectId);
        $destination = 'https://example.test/resolved-project';

        $this->get($this->trackingUrl($subscriber, $destination, ['template_id' => $template->id]))
            ->assertRedirect($destination);

        $this->assertDatabaseHas('redirect', [
            'template_id' => $template->id, 'template' => $template->name,
            'url' => $destination, 'email' => $subscriber->email,
        ]);
    }

    #[DataProvider('invalidTemplateIds')]
    public function test_invalid_template_parameter_does_not_create_a_click_record(mixed $templateId): void
    {
        $subscriber = $this->reader([Project::DEFAULT_ID]);

        $this->get($this->trackingUrl($subscriber, 'https://example.test/rejected', [
            'project_id' => Project::DEFAULT_ID, 'template_id' => $templateId,
        ]))->assertNotFound();

        $this->assertDatabaseCount('redirect', 0);
    }

    public function test_template_from_another_project_is_rejected_even_when_subscriber_belongs_to_both_projects(): void
    {
        $projectId = $this->testProjectId();
        $subscriber = $this->reader([Project::DEFAULT_ID, $projectId]);
        $template = $this->newsletter($projectId);

        $this->get($this->trackingUrl($subscriber, 'https://example.test/wrong-project', [
            'project_id' => Project::DEFAULT_ID, 'template_id' => $template->id,
        ]))->assertNotFound();

        $this->assertDatabaseCount('redirect', 0);
    }

    public function test_template_does_not_allow_tracking_for_a_subscriber_outside_its_project(): void
    {
        $subscriber = $this->reader([Project::DEFAULT_ID]);
        $template = $this->newsletter($this->testProjectId());

        $this->get($this->trackingUrl($subscriber, 'https://example.test/not-a-member', [
            'template_id' => $template->id,
        ]))->assertNotFound();

        $this->assertDatabaseCount('redirect', 0);
    }

    public static function projects(): array
    {
        return ['virtual default project' => [true], 'stored project' => [false]];
    }

    public static function legacyProjects(): array
    {
        return [
            'default explicit project' => [true, true],
            'default inferred project' => [true, false],
            'stored explicit project' => [false, true],
            'stored inferred project' => [false, false],
        ];
    }

    public static function invalidTemplateIds(): array
    {
        return [
            'zero' => [0],
            'negative' => [-1],
            'text' => ['invalid'],
            'partial integer' => ['1invalid'],
            'fraction' => ['1.5'],
            'array' => [[1]],
            'unknown template' => [2147483647],
        ];
    }

    private function reader(array $projectIds): Subscribers
    {
        return $this->subscriberFixture([
            'name' => 'Newsletter reader', 'email' => 'redirect-reader@example.test',
            'active' => 1, 'token' => str_repeat('r', 32),
        ], $projectIds);
    }

    private function newsletter(int $projectId, string $name = 'Tracked newsletter', string $body = '<p>Hello</p>'): Templates
    {
        return Templates::query()->create([
            'project_id' => $projectId, 'name' => $name, 'body' => $body, 'prior' => 0,
        ]);
    }

    private function trackingUrl(Subscribers $subscriber, string $destination, array $parameters = []): string
    {
        return route('frontend.referral', [
            'subscriber' => $subscriber->id,
            'ref' => rtrim(strtr(base64_encode($destination), '+/', '-_'), '='),
        ] + $parameters);
    }

    private function configureMail(): void
    {
        URL::forceRootUrl('http://newsletter.test');
        foreach ([
            'HOW_TO_SEND' => 'mail', 'SENDMAIL_PATH' => '', 'EMAIL' => 'sender@example.test',
            'FROM' => 'Newsletter tests', 'LIST_OWNER' => '', 'RETURN_PATH' => '',
            'CONTENT_TYPE' => 'html', 'ORGANIZATION' => '', 'REQUEST_REPLY' => '0',
            'PRECEDENCE' => 'bulk', 'SHOW_UNSUBSCRIBE_LINK' => '0', 'UNSUBLINK' => '',
            'URL' => 'http://newsletter.test', 'REMOVE_SUBSCRIBER' => '0',
        ] as $name => $value) {
            Settings::query()->updateOrCreate(['name' => $name], ['value' => $value]);
        }
    }
}

class RedirectMimeOnlySendEmailHelper extends SendEmailHelper
{
    public readonly RedirectMimeOnlyMailer $mailer;

    public function __construct()
    {
        $this->mailer = new RedirectMimeOnlyMailer;
    }

    protected function createMailer(): PHPMailer
    {
        return $this->mailer;
    }
}

class RedirectMimeOnlyMailer extends PHPMailer
{
    public string $capturedMime = '';

    public function send(): bool
    {
        if (! $this->preSend()) {
            return false;
        }

        $this->capturedMime = $this->getSentMIMEMessage();

        return true;
    }
}
