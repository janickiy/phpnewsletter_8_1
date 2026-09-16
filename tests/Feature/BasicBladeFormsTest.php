<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Macros;
use App\Models\Schedule;
use App\Models\ScheduleCategory;
use App\Models\Templates;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class BasicBladeFormsTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_restores_safe_input_but_never_the_password(): void
    {
        $login = 'admin"><script id="injected">alert(1)</script>';
        $response = $this->withSession(['_old_input' => [
            'login' => $login,
            'password' => 'must-not-be-rendered',
            'remember' => '1',
        ]])->get(route('login'))->assertOk();

        $xpath = $this->parse($response->getContent());
        $this->assertSame(route('login'), $xpath->evaluate('string(//form/@action)'));
        $this->assertNotSame('', $xpath->evaluate('string(//form/input[@name="_token"]/@value)'));
        $this->assertSame($login, $xpath->evaluate('string(//input[@name="login"]/@value)'));
        $this->assertSame('', $xpath->evaluate('string(//input[@name="password"]/@value)'));
        $this->assertSame(1, $xpath->query('//input[@name="remember"][@checked]')->length);
        $this->assertSame(0, $xpath->query('//*[@id="injected"]')->length);

        $response = $this->withSession(['_old_input' => ['login' => 'admin']])
            ->get(route('login'))->assertOk();
        $this->assertSame(0, $this->parse($response->getContent())->query('//input[@name="remember"][@checked]')->length);
    }

    public function test_installer_forms_have_csrf_tokens_and_keep_passwords_empty(): void
    {
        $this->withSession(['_token' => 'form-token', '_old_input' => [
            'host' => 'db-host',
            'username' => 'newsletter-user',
            'database' => 'newsletter-db',
            'login' => 'newsletter-admin',
            'password' => 'must-not-be-rendered',
            'confirm_password' => 'must-not-be-rendered',
        ]]);

        foreach (['database' => 'install.installation', 'installation' => 'install.install'] as $view => $route) {
            $html = view('install.'.$view, ['errors' => new ViewErrorBag])->render();
            $xpath = $this->parse($html);
            $this->assertSame(route($route), $xpath->evaluate('string(//form/@action)'));
            $this->assertSame('POST', $xpath->evaluate('string(//form/@method)'));
            $this->assertSame('form-token', $xpath->evaluate('string(//form/input[@name="_token"]/@value)'));
            $this->assertSame(0, $xpath->query('//input[@type="password"][@value]')->length);
            $this->assertStringNotContainsString('must-not-be-rendered', $html);
        }
    }

    public function test_subscription_form_restores_multiple_categories_and_escapes_their_labels(): void
    {
        $unselected = Category::query()->create(['name' => 'One']);
        $selected = Category::query()->create(['name' => '<script id="injected">unsafe</script>']);
        $anotherSelected = Category::query()->create(['name' => 'Three']);

        $response = $this->withSession(['_token' => 'subscription-token', '_old_input' => [
            'categoryId' => [(string) $selected->id, (string) $anotherSelected->id],
            'name' => 'Subscriber & friend',
            'email' => 'subscriber@example.com',
        ]])->get(route('frontend.form'))->assertOk();

        $xpath = $this->parse($response->getContent());
        $this->assertSame(2, $xpath->query('//input[@name="categoryId[]"][@checked]')->length);
        $this->assertSame(0, $xpath->query('//input[@name="categoryId[]"][@value="'.$unselected->id.'"][@checked]')->length);
        $this->assertSame(0, $xpath->query('//*[@id="injected"]')->length);
        $this->assertSame('Subscriber & friend', $xpath->evaluate('string(//input[@name="name"]/@value)'));
        $this->assertSame('subscriber@example.com', $xpath->evaluate('string(//input[@name="email"]/@value)'));
        $this->assertSame('subscription-token', $xpath->evaluate('string(//form[@id="addsub"]/input[@name="_token"]/@value)'));
        $this->assertSame('button', $xpath->evaluate('string(//button[@id="sub"]/@type)'));
    }

    public function test_category_edit_form_keeps_put_method_and_empty_old_name(): void
    {
        $this->signIn();
        $category = Category::query()->create(['name' => 'Saved category']);
        $response = $this->withSession(['_old_input' => ['name' => null]])
            ->get(route('admin.category.edit', ['id' => $category->id]))->assertOk();

        $xpath = $this->parse($response->getContent());
        $this->assertSame(route('admin.category.update'), $xpath->evaluate('string(//form/@action)'));
        $this->assertSame('PUT', $xpath->evaluate('string(//input[@name="_method"]/@value)'));
        $this->assertSame((string) $category->id, $xpath->evaluate('string(//input[@name="id"]/@value)'));
        $this->assertSame('', $xpath->evaluate('string(//input[@name="name"]/@value)'));
    }

    public function test_macro_edit_restores_the_submitted_type_and_escaped_textarea(): void
    {
        $this->signIn();
        $macro = Macros::query()->create(['name' => 'Saved macro', 'value' => 'Saved value', 'type' => 1]);
        $value = '</textarea><script id="injected">unsafe</script>';
        $response = $this->withSession(['_old_input' => ['type' => '4', 'value' => $value]])
            ->get(route('admin.macros.edit', ['id' => $macro->id]))->assertOk();

        $xpath = $this->parse($response->getContent());
        $this->assertSame('4', $xpath->evaluate('string(//select[@id="type"]/option[@selected]/@value)'));
        $this->assertSame($value, $xpath->evaluate('string(//textarea[@id="value"])'));
        $this->assertSame(0, $xpath->query('//*[@id="injected"]')->length);
    }

    public function test_schedule_edit_keeps_saved_choices_then_clears_omitted_old_categories(): void
    {
        $this->signIn();
        $category = Category::query()->create(['name' => 'Newsletter readers']);
        $template = Templates::query()->create(['name' => 'Newsletter', 'body' => '<p>News</p>', 'prior' => 0]);
        $schedule = Schedule::query()->create([
            'event_name' => 'Mailing',
            'event_start' => now()->addDays(2),
            'event_end' => now()->addDays(3),
            'template_id' => $template->id,
        ]);
        ScheduleCategory::query()->create(['schedule_id' => $schedule->id, 'category_id' => $category->id]);

        $response = $this->get(route('admin.schedule.edit', ['id' => $schedule->id]))->assertOk();
        $xpath = $this->parse($response->getContent());
        $this->assertSame((string) $category->id, $xpath->evaluate('string(//select[@id="categoryId"]/option[@selected]/@value)'));
        $this->assertSame((string) $template->id, $xpath->evaluate('string(//select[@id="template_id"]/option[@selected]/@value)'));

        $response = $this->withSession(['_old_input' => ['event_name' => 'Changed mailing', 'template_id' => null]])
            ->get(route('admin.schedule.edit', ['id' => $schedule->id]))->assertOk();
        $xpath = $this->parse($response->getContent());
        $this->assertSame(0, $xpath->query('//select[@id="categoryId"]/option[@selected]')->length);
        $this->assertSame('', $xpath->evaluate('string(//select[@id="template_id"]/option[@selected]/@value)'));
        $this->assertSame('Changed mailing', $xpath->evaluate('string(//input[@name="event_name"]/@value)'));
        $this->assertSame('PUT', $xpath->evaluate('string(//input[@name="_method"]/@value)'));
    }

    private function signIn(): void
    {
        $this->actingAs(User::query()->create([
            'name' => 'Forms administrator',
            'login' => 'forms-admin',
            'role' => User::ROLE_ADMIN,
            'password' => 'secret123',
        ]));
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
