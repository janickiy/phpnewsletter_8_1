<?php

namespace Tests\Feature;

use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLteFormLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::query()->create([
            'name' => 'Layout administrator',
            'login' => 'layout-admin',
            'role' => User::ROLE_ADMIN,
            'password' => 'secret123',
        ]));
    }

    public function test_settings_tabs_target_their_panels_without_submitting_the_form(): void
    {
        $page = $this->page(route('admin.settings.index'));
        $tabs = $page->query('//button[@role="tab"]');
        $this->assertSame(3, $tabs->length);

        foreach ($tabs as $tab) {
            $this->assertSame('button', $tab->getAttribute('type'));
            $this->assertSame('tab', $tab->getAttribute('data-bs-toggle'));
            $panelId = substr($tab->getAttribute('data-bs-target'), 1);
            $panel = $page->query('//*[@id="'.$panelId.'"][@role="tabpanel"]')->item(0);
            $this->assertNotNull($panel);
            $this->assertSame($tab->getAttribute('id'), $panel->getAttribute('aria-labelledby'));
        }

        $this->assertSame(1, $page->query('//button[@role="tab"][@aria-selected="true"]')->length);
        $this->assertSame(1, $page->query('//*[@role="tabpanel"][contains(concat(" ", normalize-space(@class), " "), " active ")]')->length);
    }

    public function test_upload_and_radio_controls_keep_native_labels_and_submission_values(): void
    {
        $response = $this->get(route('admin.subscribers.import'))->assertOk();
        $response->assertDontSee('bs-custom-file-input', false);
        $page = $this->parse($response->getContent());
        $this->assertSame('multipart/form-data', $page->evaluate('string(//form/@enctype)'));
        $this->assertSame('form-control', $page->evaluate('string(//input[@type="file"][@name="import"]/@class)'));
        $this->assertSame(1, $page->query('//label[@for="import"]')->length);

        $page = $this->page(route('admin.subscribers.export'), [
            '_old_input' => ['export_type' => 'excel', 'compress' => 'zip'],
        ]);
        $radios = $page->query('//input[@type="radio"]');
        $this->assertSame(4, $radios->length);

        foreach ($radios as $radio) {
            $this->assertSame('form-check-input', $radio->getAttribute('class'));
            $this->assertSame(1, $page->query('//label[@for="'.$radio->getAttribute('id').'"]')->length);
        }

        $this->assertSame('excel', $page->evaluate('string(//input[@name="export_type"][@checked]/@value)'));
        $this->assertSame('zip', $page->evaluate('string(//input[@name="compress"][@checked]/@value)'));
    }

    public function test_success_notification_has_a_bootstrap_dismiss_control(): void
    {
        $message = 'Settings saved <script>unsafe</script>';
        $page = $this->page(route('admin.settings.index'), ['success' => $message]);
        $alert = '//div[contains(concat(" ", normalize-space(@class), " "), " alert-success ")]';

        $this->assertSame(1, $page->query($alert.'[@role="alert"]')->length);
        $this->assertSame(1, $page->query($alert.'//button[@type="button"][@data-bs-dismiss="alert"][@aria-label!=""]')->length);
        $this->assertSame(0, $page->query($alert.'//script')->length);
        $this->assertStringContainsString($message, $page->query($alert)->item(0)->textContent);
    }

    private function page(string $url, array $session = []): DOMXPath
    {
        $response = $this->withSession($session)->get($url)->assertOk();

        return $this->parse($response->getContent());
    }

    private function parse(string $html): DOMXPath
    {
        $document = new DOMDocument;
        $previousState = libxml_use_internal_errors(true);
        $document->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previousState);

        return new DOMXPath($document);
    }
}
