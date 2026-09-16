<?php

namespace Tests\Feature;

use App\Helpers\SendEmailHelper;
use App\Models\Attach;
use App\Models\CustomHeaders;
use App\Models\Settings;
use App\Models\Templates;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use PHPMailer\PHPMailer\PHPMailer;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class MailerCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_personalized_html_message_preserves_headers_tracking_and_attachment_mime(): void
    {
        $this->configureMailSettings();
        Storage::fake('local');
        config(['filesystems.default' => 'local']);

        $template = Templates::query()->create([
            'name' => 'Attachment compatibility',
            'body' => '<p>Привет, %NAME%!</p>',
            'prior' => 0,
        ]);
        $attachmentContents = "Attachment payload\nwith binary bytes: \x00\x01\xFF";
        Storage::disk('local')->put(Attach::DIRECTORY.'/stored-report.txt', $attachmentContents);
        Attach::query()->create([
            'name' => 'report.txt',
            'file_name' => 'stored-report.txt',
            'template_id' => $template->id,
        ]);
        CustomHeaders::query()->create([
            'name' => 'X-Campaign',
            'value' => 'compatibility-check',
        ]);

        $helper = new MimeOnlySendEmailHelper;
        $helper->subject = 'Рассылка для %NAME%';
        $helper->body = $template->body;
        $helper->email = 'recipient@example.test';
        $helper->name = 'Анна';
        $helper->templateId = $template->id;
        $helper->subscriberId = 42;
        $helper->token = 'subscriber-token';

        $this->assertSame(['result' => true, 'error' => null], $helper->sendEmail($template->id));

        $mime = $helper->mailer->capturedMime;
        $this->assertSame('Рассылка для Анна', $this->mimeHeader($mime, 'Subject'));
        $this->assertStringContainsString('Content-Type: multipart/mixed;', $mime);
        $this->assertStringContainsString('Content-Type: text/html; charset=utf-8', $mime);
        $this->assertStringContainsString('<p>Привет, Анна!</p>', $mime);
        $this->assertStringNotContainsString('%NAME%', $mime);
        $this->assertSame('compatibility-check', $this->mimeHeader($mime, 'X-Campaign'));
        $this->assertSame('Newsletter tests', $this->mimeHeader($mime, 'Organization'));

        $unsubscribeUrl = route('frontend.unsubscribe', [
            'subscriber' => 42,
            'token' => 'subscriber-token',
        ]);
        $trackingUrl = route('frontend.pic', ['subscriber' => 42, 'template' => $template->id]);
        $this->assertSame($unsubscribeUrl, $this->mimeHeader($mime, 'List-Unsubscribe'));
        $this->assertStringContainsString('<a href="'.$unsubscribeUrl.'">Отписаться</a>', $mime);
        $this->assertStringContainsString('src="'.$trackingUrl.'"', $mime);
        $this->assertStringContainsString('Content-Type: text/plain; name=report.txt', $mime);
        $this->assertStringContainsString('Content-Transfer-Encoding: base64', $mime);
        $this->assertStringContainsString('Content-Disposition: attachment; filename=report.txt', $mime);
        $this->assertStringContainsString(base64_encode($attachmentContents), $mime);

        // The application must clear per-recipient state after composing a message.
        $this->assertSame([], $helper->mailer->getAllRecipientAddresses());
        $this->assertSame([], $helper->mailer->getCustomHeaders());
        $this->assertSame([], $helper->mailer->getAttachments());
    }

    public function test_plain_text_message_respects_disabled_tracking_and_unsubscribe(): void
    {
        $this->configureMailSettings(['CONTENT_TYPE' => 'text']);

        $helper = new MimeOnlySendEmailHelper;
        $helper->subject = 'Сообщение';
        $helper->body = '<p>Здравствуйте, %NAME%!</p><br>Вторая строка';
        $helper->email = 'recipient@example.test';
        $helper->name = 'Борис';
        $helper->tracking = false;
        $helper->unsub = false;

        $this->assertSame(['result' => true, 'error' => null], $helper->sendEmail());

        $mime = $helper->mailer->capturedMime;
        $this->assertStringContainsString('Content-Type: text/plain; charset=utf-8', $mime);
        $this->assertStringContainsString('Здравствуйте, Борис!', $mime);
        $this->assertStringContainsString('Вторая строка', $mime);
        $this->assertStringNotContainsString('<p>', $mime);
        $this->assertStringNotContainsString('<img', $mime);
        $this->assertStringNotContainsString('List-Unsubscribe:', $mime);
        $this->assertStringNotContainsString('Отписаться', $mime);
    }

    #[DataProvider('messageContentTypes')]
    public function test_legacy_replacement_flags_cannot_change_cyrillic_subject_or_body(string $contentType): void
    {
        $this->configureMailSettings([
            'CONTENT_TYPE' => $contentType,
            'RENDOM_REPLACEMENT_SUBJECT' => '1',
            'RANDOM_REPLACEMENT_BODY' => '1',
        ]);

        $text = str_repeat('АВЕКМНОРСТХ аеосу ', 5);
        $helper = new MimeOnlySendEmailHelper;
        $helper->subject = $text.'для %NAME%';
        $helper->body = '<p>'.$text.'для %NAME%</p>';
        $helper->email = 'recipient@example.test';
        $helper->name = 'Александр';
        $helper->tracking = false;
        $helper->unsub = false;

        $this->assertSame(['result' => true, 'error' => null], $helper->sendEmail());

        $expected = $text.'для Александр';
        $this->assertSame($expected, $helper->mailer->Subject);
        $this->assertSame($expected, $this->mimeHeader($helper->mailer->capturedMime, 'Subject'));
        $this->assertSame(
            $contentType === 'html' ? '<p>'.$expected.'</p>' : $expected,
            $helper->mailer->Body,
        );
    }

    public static function messageContentTypes(): array
    {
        return [
            'HTML' => ['html'],
            'plain text' => ['text'],
        ];
    }

    #[DataProvider('obsoleteCharsetSettings')]
    public function test_outgoing_messages_use_utf8_independently_of_obsolete_charset_settings(?string $legacyCharset, string $contentType): void
    {
        $senderName = 'Отправитель 日本 🚀';
        $recipientName = 'Анна 日本 🌍';
        $attachmentName = 'Отчёт_日本_🚀.txt';
        $settings = ['CONTENT_TYPE' => $contentType, 'FROM' => $senderName];

        Settings::query()->where('name', 'CHARSET')->delete();
        if ($legacyCharset !== null) {
            $settings['CHARSET'] = $legacyCharset;
        }
        $this->configureMailSettings($settings);
        Storage::fake('local');
        config(['filesystems.default' => 'local']);

        $template = Templates::query()->create([
            'name' => 'Новости 日本 🚀 для %NAME%',
            'body' => '<p>Здравствуйте, %NAME%! Новости 日本 🚀</p>',
            'prior' => 0,
        ]);
        $attachmentContents = 'Содержимое 日本 🚀';
        Storage::disk('local')->put(Attach::DIRECTORY.'/unicode-report.txt', $attachmentContents);
        Attach::query()->create([
            'name' => $attachmentName,
            'file_name' => 'unicode-report.txt',
            'template_id' => $template->id,
        ]);

        $helper = new MimeOnlySendEmailHelper;
        $helper->subject = $template->name;
        $helper->body = $template->body;
        $helper->email = 'recipient@example.test';
        $helper->name = $recipientName;
        $helper->templateId = $template->id;
        $helper->tracking = false;
        $helper->unsub = false;

        $this->assertSame(['result' => true, 'error' => null], $helper->sendEmail($template->id));

        $mime = $helper->mailer->capturedMime;
        $this->assertSame('utf-8', $helper->mailer->CharSet);
        $this->assertSame('Новости 日本 🚀 для '.$recipientName, $this->mimeHeader($mime, 'Subject'));
        $this->assertSame($senderName.' <sender@example.test>', $this->mimeHeader($mime, 'From'));
        $this->assertSame('recipient@example.test', $this->mimeHeader($mime, 'To'));
        $mimeContentType = $contentType === 'html' ? 'text/html' : 'text/plain';
        $this->assertStringContainsString('Content-Type: '.$mimeContentType.'; charset=utf-8', $mime);
        $this->assertStringContainsString('Здравствуйте, '.$recipientName.'! Новости 日本 🚀', $mime);
        $this->assertStringNotContainsString('%NAME%', $mime);
        $this->assertStringNotContainsString('windows-1251', $mime);
        $this->assertStringNotContainsString('not-a-real-charset', $mime);

        $unfoldedMime = preg_replace('/\r\n[ \t]+/', ' ', $mime);
        $this->assertSame(1, preg_match('/Content-Disposition: attachment;[ \t]*filename="?([^"\r\n]+)"?/', $unfoldedMime, $attachmentHeader));
        $this->assertSame($attachmentName, mb_decode_mimeheader($attachmentHeader[1]));
        $this->assertStringContainsString(base64_encode($attachmentContents), $mime);
    }

    public static function obsoleteCharsetSettings(): array
    {
        return [
            'no setting, HTML' => [null, 'html'],
            'no setting, plain text' => [null, 'text'],
            'legacy Windows-1251, HTML' => ['windows-1251', 'html'],
            'legacy Windows-1251, plain text' => ['windows-1251', 'text'],
            'invalid setting, HTML' => ['not-a-real-charset', 'html'],
            'invalid setting, plain text' => ['not-a-real-charset', 'text'],
        ];
    }

    private function configureMailSettings(array $overrides = []): void
    {
        URL::forceRootUrl('http://newsletter.test');

        foreach (array_replace([
            'HOW_TO_SEND' => 'mail',
            'SENDMAIL_PATH' => '',
            'EMAIL' => 'sender@example.test',
            'FROM' => 'Отправитель',
            'LIST_OWNER' => '',
            'RETURN_PATH' => '',
            'CONTENT_TYPE' => 'html',
            'ORGANIZATION' => 'Newsletter tests',
            'REQUEST_REPLY' => '0',
            'PRECEDENCE' => 'bulk',
            'SHOW_UNSUBSCRIBE_LINK' => '1',
            'UNSUBLINK' => '<a href="%UNSUB%">Отписаться</a>',
            'URL' => 'http://newsletter.test',
        ], $overrides) as $name => $value) {
            Settings::query()->updateOrCreate(['name' => $name], ['value' => $value]);
        }
    }

    private function mimeHeader(string $mime, string $name): string
    {
        $headers = explode("\r\n\r\n", $mime, 2)[0];
        $this->assertSame(1, preg_match('/^'.preg_quote($name, '/').':[ \t]*([^\r\n]*(?:\r\n[ \t]+[^\r\n]*)*)/m', $headers, $matches));

        // PHPMailer may fold and encode even ASCII values containing punctuation.
        return mb_decode_mimeheader(preg_replace('/\r\n[ \t]+/', ' ', trim($matches[1])));
    }
}

class MimeOnlySendEmailHelper extends SendEmailHelper
{
    public readonly MimeOnlyMailer $mailer;

    public function __construct()
    {
        $this->mailer = new MimeOnlyMailer;
    }

    protected function createMailer(): PHPMailer
    {
        return $this->mailer;
    }
}

class MimeOnlyMailer extends PHPMailer
{
    public string $capturedMime = '';

    public function send(): bool
    {
        // Exercise PHPMailer's real encoder while preventing all transport calls.
        if (! $this->preSend()) {
            return false;
        }

        $this->capturedMime = $this->getSentMIMEMessage();

        return true;
    }
}
