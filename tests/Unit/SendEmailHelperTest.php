<?php

namespace Tests\Unit;

use App\Helpers\SendEmailHelper;
use PHPMailer\PHPMailer\PHPMailer;
use PHPUnit\Framework\TestCase;

class SendEmailHelperTest extends TestCase
{
    public function test_helper_has_safe_defaults_for_optional_send_context(): void
    {
        $helper = new SendEmailHelper();

        $this->assertSame(0, $helper->prior);
    }

    public function test_mailer_identification_header_is_disabled(): void
    {
        $mailer = (new InspectableSendEmailHelper())->createMailerForTest();

        $this->assertNull($mailer->XMailer);
        $this->assertStringNotContainsString('X-Mailer:', $mailer->createHeader());
    }
}

class InspectableSendEmailHelper extends SendEmailHelper
{
    public function createMailerForTest(): PHPMailer
    {
        return $this->createMailer();
    }
}
