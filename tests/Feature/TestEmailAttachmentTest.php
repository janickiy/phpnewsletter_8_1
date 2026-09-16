<?php

namespace Tests\Feature;

use App\Helpers\SendEmailHelper;
use App\Models\Attach;
use App\Models\Templates;
use App\Repositories\ProcessRepository;
use App\Repositories\ReadySentRepository;
use App\Repositories\SubscriberRepository;
use App\Services\EmailLinkService;
use App\Services\MailingDelayService;
use App\Services\SendMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class TestEmailAttachmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_template_attachments_are_passed_to_test_email(): void
    {
        $template = Templates::query()->create([
            'name' => 'Template with attachment',
            'body' => '<p>Test body</p>',
            'prior' => 0,
        ]);

        Attach::query()->create([
            'name' => 'report.txt',
            'file_name' => 'stored-report.txt',
            'template_id' => $template->id,
        ]);

        $emailHelper = new RecordingSendEmailHelper();
        $service = new TestableSendMailService(
            app(ReadySentRepository::class),
            app(SubscriberRepository::class),
            app(ProcessRepository::class),
            app(MailingDelayService::class),
            app(EmailLinkService::class),
            $emailHelper,
        );

        $result = $service->sendTest(Request::create('/ajax', 'POST', [
            'id' => $template->id,
            'name' => $template->name,
            'body' => $template->body,
            'prior' => $template->prior,
            'email' => 'recipient@example.test',
        ]));

        $this->assertTrue($result['result']);
        $this->assertSame($template->id, $emailHelper->attachmentTemplateId);
        $this->assertSame($template->id, $emailHelper->templateId);
        $this->assertFalse($emailHelper->tracking);
    }
}

class TestableSendMailService extends SendMailService
{
    public function __construct(
        ReadySentRepository $readySentRepository,
        SubscriberRepository $subscriberRepository,
        ProcessRepository $processRepository,
        MailingDelayService $mailingDelayService,
        EmailLinkService $emailLinkService,
        private readonly RecordingSendEmailHelper $emailHelper,
    ) {
        parent::__construct(
            $readySentRepository,
            $subscriberRepository,
            $processRepository,
            $mailingDelayService,
            $emailLinkService,
        );
    }

    protected function createSendEmailHelper(): SendEmailHelper
    {
        return $this->emailHelper;
    }
}

class RecordingSendEmailHelper extends SendEmailHelper
{
    public ?int $attachmentTemplateId = null;

    public function sendEmail(?int $attach = null): array
    {
        $this->attachmentTemplateId = $attach;

        return [
            'result' => true,
            'error' => null,
        ];
    }
}
