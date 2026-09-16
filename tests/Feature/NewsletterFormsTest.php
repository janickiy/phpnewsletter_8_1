<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Subscribers;
use App\Models\Subscriptions;
use App\Models\Templates;
use App\Models\User;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewsletterFormsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::query()->create([
            'name' => 'Newsletter forms administrator',
            'login' => 'newsletter-forms-admin',
            'role' => User::ROLE_ADMIN,
            'password' => 'password',
        ]));
    }

    public function test_template_forms_keep_uploads_methods_and_escape_saved_content(): void
    {
        $page = $this->page('admin.templates.create');
        $form = $this->node($page, '//form[@id="tmplForm"]');
        $this->assertSame(route('admin.templates.store'), $form->getAttribute('action'));
        $this->assertSame('POST', $form->getAttribute('method'));
        $this->assertSame('multipart/form-data', $form->getAttribute('enctype'));
        $this->assertCsrfToken($page);
        $this->assertSame(0, $page->query('//input[@name="_method"]')->length);
        $this->assertTrue($this->node($page, '//input[@id="attachfile" and @name="attachfile[]" and @type="file"]')->hasAttribute('multiple'));
        $this->assertTrue($this->node($page, '//input[@id="prior_normal"]')->hasAttribute('checked'));

        $template = Templates::query()->create([
            'name' => '"><script id="injected">alert(1)</script>',
            'body' => '</textarea><script id="injected">alert(2)</script><p>Hello & welcome</p>',
            'prior' => 2,
        ]);
        $page = $this->page('admin.templates.edit', ['id' => $template->id]);

        $this->assertSame(route('admin.templates.update'), $this->node($page, '//form[@id="tmplForm"]')->getAttribute('action'));
        $this->assertSame('PUT', $this->node($page, '//input[@name="_method"]')->getAttribute('value'));
        $this->assertSame((string) $template->id, $this->node($page, '//input[@name="id"]')->getAttribute('value'));
        $this->assertSame($template->name, $this->node($page, '//input[@id="name"]')->getAttribute('value'));
        $this->assertSame($template->body, $this->node($page, '//textarea[@id="body"]')->textContent);
        $this->assertSame(0, $page->query('//*[@id="injected"]')->length);
        $this->assertTrue($this->node($page, '//input[@id="prior_low"]')->hasAttribute('checked'));
    }

    public function test_template_validation_repopulates_body_independently_of_name_and_preserves_priority(): void
    {
        $template = Templates::query()->create(['name' => 'Saved name', 'body' => 'Saved body', 'prior' => 0]);
        $body = '</textarea><script id="injected">alert(1)</script>';
        $this->withSession(['_old_input' => [
            'name' => 'Submitted name',
            'body' => $body,
            'prior' => '1',
            'email' => 'test@example.test',
        ]]);

        $page = $this->page('admin.templates.edit', ['id' => $template->id]);

        $this->assertSame('Submitted name', $this->node($page, '//input[@name="name"]')->getAttribute('value'));
        $this->assertSame($body, $this->node($page, '//textarea[@name="body"]')->textContent);
        $this->assertSame('test@example.test', $this->node($page, '//input[@id="email"]')->getAttribute('value'));
        $this->assertSame(0, $page->query('//*[@id="injected"]')->length);
        $this->assertTrue($this->node($page, '//input[@id="prior_high"]')->hasAttribute('checked'));
        $this->assertSame(1, $page->query('//input[@name="prior" and @checked]')->length);
    }

    public function test_subscriber_edit_preserves_values_and_restores_submitted_category_selections(): void
    {
        $savedCategory = Category::query()->create(['name' => 'Saved category']);
        $newCategory = Category::query()->create(['name' => '<b id="injected">Submitted category</b>']);
        $subscriber = Subscribers::query()->create([
            'name' => 'Subscriber "name" & company',
            'email' => 'saved@example.test',
            'active' => 1,
            'token' => str_repeat('a', 32),
        ]);
        Subscriptions::query()->create(['subscriber_id' => $subscriber->id, 'category_id' => $savedCategory->id]);
        $page = $this->page('admin.subscribers.edit', ['id' => $subscriber->id]);

        $this->assertSame(route('admin.subscribers.update'), $this->node($page, '//form')->getAttribute('action'));
        $this->assertSame('PUT', $this->node($page, '//input[@name="_method"]')->getAttribute('value'));
        $this->assertSame((string) $subscriber->id, $this->node($page, '//input[@name="id"]')->getAttribute('value'));
        $this->assertSame($subscriber->name, $this->node($page, '//input[@name="name"]')->getAttribute('value'));
        $this->assertSame($subscriber->email, $this->node($page, '//input[@name="email"]')->getAttribute('value'));
        $this->assertSame((string) $savedCategory->id, $this->node($page, '//select[@name="categoryId[]"]/option[@selected]')->getAttribute('value'));
        $this->assertCsrfToken($page);

        $this->withSession(['_old_input' => [
            'name' => '"><script id="injected">alert(1)</script>',
            'email' => 'submitted@example.test',
            'categoryId' => [(string) $newCategory->id],
        ]]);
        $page = $this->page('admin.subscribers.edit', ['id' => $subscriber->id]);

        $this->assertSame('submitted@example.test', $this->node($page, '//input[@name="email"]')->getAttribute('value'));
        $this->assertSame((string) $newCategory->id, $this->node($page, '//select[@id="categoryId"]/option[@selected]')->getAttribute('value'));
        $this->assertSame(0, $page->query('//*[@id="injected"]')->length);
        $this->assertTrue($this->node($page, '//select[@id="categoryId"]')->hasAttribute('multiple'));

        $this->withSession(['_old_input' => ['name' => 'No categories selected', 'email' => 'invalid-email']]);
        $page = $this->page('admin.subscribers.edit', ['id' => $subscriber->id]);

        $this->assertSame(0, $page->query('//select[@id="categoryId"]/option[@selected]')->length);
    }

    public function test_subscriber_import_keeps_upload_filters_and_categories_without_a_charset_control(): void
    {
        $firstCategory = Category::query()->create(['name' => 'First category']);
        $secondCategory = Category::query()->create(['name' => 'Second category']);
        $this->withSession(['_old_input' => [
            'charset' => 'Windows-1251',
            'categoryId' => [(string) $firstCategory->id, $secondCategory->id],
        ]]);
        $page = $this->page('admin.subscribers.import');
        $form = $this->node($page, '//form');

        $this->assertSame(route('admin.subscribers.import_subscribers'), $form->getAttribute('action'));
        $this->assertSame('POST', $form->getAttribute('method'));
        $this->assertSame('multipart/form-data', $form->getAttribute('enctype'));
        $this->assertCsrfToken($page);
        $this->assertSame('.csv,.xlsx,.xls,.ods,.txt', $this->node($page, '//input[@type="file" and @id="import" and @name="import"]')->getAttribute('accept'));
        $this->assertSame(0, $page->query('//*[@name="charset" or @id="charset"]')->length);
        $this->assertSame(2, $page->query('//select[@name="categoryId[]"]/option[@selected]')->length);
        $this->assertTrue($this->node($page, '//select[@name="categoryId[]"]')->hasAttribute('multiple'));
    }

    public function test_subscriber_export_uses_text_defaults_and_restores_export_options(): void
    {
        $category = Category::query()->create(['name' => 'Export category']);
        $page = $this->page('admin.subscribers.export');

        $this->assertSame(route('admin.subscribers.export_subscribers'), $this->node($page, '//form')->getAttribute('action'));
        $this->assertCsrfToken($page);
        $this->assertSame('text', $this->node($page, '//input[@name="export_type" and @checked]')->getAttribute('value'));
        $this->assertSame('none', $this->node($page, '//input[@name="compress" and @checked]')->getAttribute('value'));

        $this->withSession(['_old_input' => [
            'export_type' => 'excel',
            'compress' => 'zip',
            'categoryId' => [(string) $category->id],
        ]]);
        $page = $this->page('admin.subscribers.export');

        $this->assertSame('excel', $this->node($page, '//input[@name="export_type" and @checked]')->getAttribute('value'));
        $this->assertSame('zip', $this->node($page, '//input[@name="compress" and @checked]')->getAttribute('value'));
        $this->assertSame((string) $category->id, $this->node($page, '//select[@name="categoryId[]"]/option[@selected]')->getAttribute('value'));
    }

    public function test_bulk_forms_preserve_zero_actions_and_javascript_hooks(): void
    {
        $category = Category::query()->create(['name' => 'Mailing category']);
        $this->withSession(['_old_input' => ['action' => '0', 'categoryId' => [$category->id]]]);
        $page = $this->page('admin.templates.index');

        $this->assertSame(route('admin.templates.status'), $this->node($page, '//form')->getAttribute('action'));
        $this->assertCsrfToken($page);
        $sendOption = $this->node($page, '//select[@id="select_action"]/option[@selected]');
        $this->assertSame('0', $sendOption->getAttribute('value'));
        $this->assertSame('sendmail', $sendOption->getAttribute('data-id'));
        $this->assertSame('open_modal', $sendOption->getAttribute('class'));
        $this->assertTrue($this->node($page, '//input[@id="apply"]')->hasAttribute('disabled'));
        $this->assertSame((string) $category->id, $this->node($page, '//select[@id="categoryId"]/option[@selected]')->getAttribute('value'));

        $page = $this->page('admin.subscribers.index');

        $this->assertSame(route('admin.subscribers.status'), $this->node($page, '//form')->getAttribute('action'));
        $this->assertCsrfToken($page);
        $this->assertSame('0', $this->node($page, '//select[@id="select_action"]/option[@selected]')->getAttribute('value'));
        $this->assertTrue($this->node($page, '//input[@id="apply"]')->hasAttribute('disabled'));
    }

    private function page(string $route, array $parameters = []): DOMXPath
    {
        $response = $this->get(route($route, $parameters))->assertOk();
        $document = new DOMDocument;
        $previousState = libxml_use_internal_errors(true);

        try {
            $document->loadHTML($response->getContent());
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousState);
        }

        return new DOMXPath($document);
    }

    private function node(DOMXPath $page, string $query): DOMElement
    {
        $nodes = $page->query($query);
        $this->assertSame(1, $nodes->length, $query);

        return $nodes->item(0);
    }

    private function assertCsrfToken(DOMXPath $page): void
    {
        $this->assertSame(session()->token(), $this->node($page, '//form/input[@name="_token"]')->getAttribute('value'));
    }
}
