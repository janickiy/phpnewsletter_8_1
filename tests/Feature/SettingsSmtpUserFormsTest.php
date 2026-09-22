<?php

namespace Tests\Feature;

use App\Models\CustomHeaders;
use App\Models\Settings;
use App\Models\Smtp;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsSmtpUserFormsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::query()->create([
            'name' => 'Form administrator',
            'login' => 'form-admin',
            'role' => User::ROLE_ADMIN,
            'password' => 'secret123',
        ]));
    }

    public function test_settings_form_preserves_saved_defaults_and_escapes_header_values(): void
    {
        Settings::query()->create(['name' => 'FROM', 'value' => 'Sender "<&>']);
        Settings::query()->create(['name' => 'REQUIRE_SUB_CONFIRMATION', 'value' => '1']);
        CustomHeaders::query()->create(['name' => 'X-Saved', 'value' => '"><script id="injected">bad</script>']);

        $xpath = $this->page(route('admin.settings.index'));

        $this->assertSame('Sender "<&>', $xpath->evaluate('string(//input[@name="FROM"]/@value)'));
        $this->assertSame(1, $xpath->query('//input[@name="REQUIRE_SUB_CONFIRMATION"][@checked]')->length);
        $this->assertSame('html', $xpath->evaluate('string(//select[@name="CONTENT_TYPE"]/option[@selected]/@value)'));
        $this->assertSame('php', $xpath->evaluate('string(//select[@name="HOW_TO_SEND"]/option[@selected]/@value)'));
        $this->assertSame('"><script id="injected">bad</script>', $xpath->evaluate('string(//input[@name="header_value[]"]/@value)'));
        $this->assertSame(0, $xpath->query('//*[@id="injected"]')->length);
        $this->assertSame(1, $xpath->query('//label[@for="header_value_0"]')->length);
        $this->assertSame('PUT', $xpath->evaluate('string(//form/input[@name="_method"]/@value)'));
        $this->assertSame('forms-csrf-token', $xpath->evaluate('string(//form/input[@name="_token"]/@value)'));
    }

    public function test_settings_old_input_keeps_unchecked_boxes_and_submitted_header_rows(): void
    {
        Settings::query()->create(['name' => 'FROM', 'value' => 'Saved sender']);
        Settings::query()->create(['name' => 'REQUIRE_SUB_CONFIRMATION', 'value' => '1']);
        CustomHeaders::query()->create(['name' => 'X-Removed', 'value' => 'Removed']);

        $xpath = $this->page(route('admin.settings.index'), [
            '_old_input' => [
                'FROM' => 'Submitted sender',
                'SHOW_UNSUBSCRIBE_LINK' => '1',
                'CONTENT_TYPE' => 'plain',
                'HOW_TO_SEND' => 'smtp',
                'INTERVAL_TYPE' => 'hour',
                'TEXT_CONFIRMATION' => '</textarea><script id="injected">bad</script>',
                'header_name' => ['X-New', 'X-Second'],
                'header_value' => ['First "value"', 'Second'],
            ],
        ]);

        $this->assertSame('Submitted sender', $xpath->evaluate('string(//input[@name="FROM"]/@value)'));
        $this->assertSame(0, $xpath->query('//input[@name="REQUIRE_SUB_CONFIRMATION"][@checked]')->length);
        $this->assertSame(1, $xpath->query('//input[@name="SHOW_UNSUBSCRIBE_LINK"][@checked]')->length);
        $this->assertSame('plain', $xpath->evaluate('string(//select[@name="CONTENT_TYPE"]/option[@selected]/@value)'));
        $this->assertSame('smtp', $xpath->evaluate('string(//select[@name="HOW_TO_SEND"]/option[@selected]/@value)'));
        $this->assertSame('hour', $xpath->evaluate('string(//select[@name="INTERVAL_TYPE"]/option[@selected]/@value)'));
        $this->assertSame('</textarea><script id="injected">bad</script>', $xpath->evaluate('string(//textarea[@name="TEXT_CONFIRMATION"])'));
        $this->assertSame(0, $xpath->query('//*[@id="injected"]')->length);
        $this->assertSame(2, $xpath->query('//input[@name="header_name[]"]')->length);
        $this->assertSame('X-New', $xpath->evaluate('string(//input[@id="header_name_0"]/@value)'));
        $this->assertSame('Second', $xpath->evaluate('string(//input[@id="header_value_1"]/@value)'));
        $this->assertSame(0, $xpath->query('//input[@value="X-Removed"]')->length);
    }

    public function test_smtp_create_and_bulk_forms_preserve_defaults_and_submission_controls(): void
    {
        $xpath = $this->page(route('admin.smtp.create'));

        $this->assertSame(route('admin.smtp.store'), $xpath->evaluate('string(//form/@action)'));
        $this->assertSame('POST', $xpath->evaluate('string(//form/@method)'));
        $this->assertSame('25', $xpath->evaluate('string(//input[@name="port"]/@value)'));
        $this->assertSame('5', $xpath->evaluate('string(//input[@name="timeout"]/@value)'));
        $this->assertSame('no', $xpath->evaluate('string(//select[@name="secure"]/option[@selected]/@value)'));
        $this->assertSame('no', $xpath->evaluate('string(//select[@name="authentication"]/option[@selected]/@value)'));
        $this->assertSame(0, $xpath->query('//input[@name="_method"]')->length);

        $xpath = $this->page(route('admin.smtp.index'), ['_old_input' => ['action' => '0']]);

        $this->assertSame(route('admin.smtp.status'), $xpath->evaluate('string(//form/@action)'));
        $this->assertSame('0', $xpath->evaluate('string(//select[@id="select_action"]/option[@selected]/@value)'));
        $this->assertSame(1, $xpath->query('//button[@id="apply"][@type="submit"][@disabled]')->length);
        $this->assertSame('forms-csrf-token', $xpath->evaluate('string(//form/input[@name="_token"]/@value)'));
    }

    public function test_smtp_edit_keeps_the_stored_secret_and_honors_old_input(): void
    {
        $smtp = Smtp::query()->create([
            'host' => 'smtp.example.test',
            'email' => 'mail@example.test',
            'username' => 'mailer',
            'password' => 'Stored "<&> secret',
            'port' => 587,
            'authentication' => 'plain',
            'secure' => 'tls',
            'timeout' => 10,
        ]);

        $xpath = $this->page(route('admin.smtp.edit', ['id' => $smtp->id]));

        $this->assertSame('Stored "<&> secret', $xpath->evaluate('string(//input[@name="password"]/@value)'));
        $this->assertSame('PUT', $xpath->evaluate('string(//form/input[@name="_method"]/@value)'));
        $this->assertSame((string) $smtp->id, $xpath->evaluate('string(//input[@name="id"]/@value)'));
        $this->assertSame('tls', $xpath->evaluate('string(//select[@name="secure"]/option[@selected]/@value)'));
        $this->assertSame('plain', $xpath->evaluate('string(//select[@name="authentication"]/option[@selected]/@value)'));

        $xpath = $this->page(route('admin.smtp.edit', ['id' => $smtp->id]), [
            '_old_input' => [
                'host' => '"><script id="injected">bad</script>',
                'password' => '',
                'secure' => 'ssl',
                'authentication' => 'crammd5',
                'port' => '465',
            ],
        ]);

        $this->assertSame('"><script id="injected">bad</script>', $xpath->evaluate('string(//input[@name="host"]/@value)'));
        $this->assertSame(0, $xpath->query('//*[@id="injected"]')->length);
        $this->assertSame('', $xpath->evaluate('string(//input[@name="password"]/@value)'));
        $this->assertSame('465', $xpath->evaluate('string(//input[@name="port"]/@value)'));
        $this->assertSame('ssl', $xpath->evaluate('string(//select[@name="secure"]/option[@selected]/@value)'));
        $this->assertSame('crammd5', $xpath->evaluate('string(//select[@name="authentication"]/option[@selected]/@value)'));
    }

    public function test_user_forms_restore_safe_fields_but_never_repopulate_passwords(): void
    {
        $xpath = $this->page(route('admin.users.create'), [
            '_old_input' => [
                'name' => '"><script id="injected">bad</script>',
                'description' => '</textarea><script id="injected">bad</script>',
                'role' => User::ROLE_PROJECT_ADMIN,
                'password' => 'never-render-this-secret',
                'password_again' => 'never-render-this-secret',
            ],
        ]);

        $this->assertSame(route('admin.users.store'), $xpath->evaluate('string(//form/@action)'));
        $this->assertSame(User::ROLE_PROJECT_ADMIN, $xpath->evaluate('string(//select[@name="role"]/option[@selected]/@value)'));
        $this->assertSame('"><script id="injected">bad</script>', $xpath->evaluate('string(//input[@name="name"]/@value)'));
        $this->assertSame('</textarea><script id="injected">bad</script>', $xpath->evaluate('string(//textarea[@name="description"])'));
        $this->assertSame(0, $xpath->query('//*[@id="injected"]')->length);
        $this->assertSame(2, $xpath->query('//input[@type="password"][@autocomplete="new-password"][not(@value)]')->length);

        $xpath = $this->page(route('admin.users.edit', ['id' => auth()->id()]), ['_old_input' => []]);

        $this->assertSame(route('admin.users.update'), $xpath->evaluate('string(//form/@action)'));
        $this->assertSame('PUT', $xpath->evaluate('string(//form/input[@name="_method"]/@value)'));
        $this->assertSame(User::ROLE_ADMIN, $xpath->evaluate('string(//input[@type="hidden"][@name="role"]/@value)'));
        $this->assertSame(0, $xpath->query('//select[@name="role"]')->length);
        $this->assertSame(2, $xpath->query('//input[@type="password"][not(@value)]')->length);
    }

    private function page(string $url, array $session = []): DOMXPath
    {
        $response = $this->withSession(['_token' => 'forms-csrf-token', ...$session])->get($url);
        $response->assertOk();

        $document = new DOMDocument;
        $previousState = libxml_use_internal_errors(true);
        $document->loadHTML($response->getContent());
        libxml_clear_errors();
        libxml_use_internal_errors($previousState);

        return new DOMXPath($document);
    }
}
