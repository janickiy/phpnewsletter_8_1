<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ApplicationBootstrapTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_locale_cookie_is_decrypted_before_selecting_translations(): void
    {
        Route::middleware('web')->get('/_test/locale', fn () => [
            'locale' => app()->getLocale(),
            'message' => __('auth.failed'),
        ]);

        $this->withCookie('lang', 'ru')
            ->withHeader('Accept-Language', 'en-US,en;q=0.9')
            ->get('/_test/locale')
            ->assertOk()
            ->assertJsonPath('locale', 'ru')
            ->assertJsonPath('message', 'Имя пользователя и пароль не совпадают.');
    }

    public function test_api_requests_select_the_best_supported_browser_language(): void
    {
        Route::middleware('api')->get('/api/_test/locale', fn () => [
            'locale' => app()->getLocale(),
            'message' => __('auth.failed'),
        ]);

        $this->withHeader('Accept-Language', 'ja-JP, en-US;q=0.5, ru-RU;q=0.9')
            ->getJson('/api/_test/locale')
            ->assertOk()
            ->assertJsonPath('locale', 'ru')
            ->assertJsonPath('message', 'Имя пользователя и пароль не совпадают.');
    }

    public function test_api_limit_allows_sixty_requests_and_recovers_after_one_minute(): void
    {
        $user = $this->createUser(User::ROLE_ADMIN);
        $this->withToken($user->createToken('bootstrap-test')->plainTextToken);

        for ($request = 0; $request < 60; $request++) {
            $this->getJson('/api/user')->assertOk();
        }

        $this->getJson('/api/user')
            ->assertTooManyRequests()
            ->assertHeader('X-RateLimit-Limit', '60')
            ->assertHeader('X-RateLimit-Remaining', '0');

        $this->travel(61)->seconds();

        $this->getJson('/api/user')->assertOk();
    }

    public function test_moderators_cannot_manage_global_categories_or_settings(): void
    {
        $this->actingAs($this->createUser(User::ROLE_MODERATOR));

        $this->get(route('admin.category.index'))->assertForbidden();
        $this->get(route('admin.settings.index'))->assertForbidden();
    }

    public function test_project_administrators_cannot_manage_global_categories(): void
    {
        $this->actingAs($this->createUser(User::ROLE_PROJECT_ADMIN))
            ->get(route('admin.category.index'))
            ->assertForbidden();
    }

    public function test_installed_application_blocks_installer_but_allows_completion_page(): void
    {
        $this->get(route('install.database'))
            ->assertRedirect(route('admin.dashboard.index'));

        $this->get(route('install.complete'))->assertOk();
    }

    public function test_uninstalled_application_redirects_web_and_api_requests_to_installer(): void
    {
        $originalBasePath = $this->app->basePath();
        $emptyBasePath = sys_get_temp_dir().'/phpnewsletter-install-'.bin2hex(random_bytes(8));
        mkdir($emptyBasePath);

        try {
            $this->app->setBasePath($emptyBasePath);

            $this->get('/')->assertRedirect(route('install.start'));
            $this->getJson('/api/user')->assertRedirect(route('install.start'));
        } finally {
            $this->app->setBasePath($originalBasePath);
            rmdir($emptyBasePath);
        }
    }

    public function test_mailing_commands_are_discovered_and_scheduled_once_with_overlap_protection(): void
    {
        $expectedSchedule = [
            'emails:send' => '* * * * *',
            'emails:unsent' => '*/10 * * * *',
            'emails:remove-unconfirmed-subscriber' => '*/10 * * * *',
        ];
        $commands = Artisan::all();
        $events = app(Schedule::class)->events();

        $this->assertCount(count($expectedSchedule), $events);

        foreach ($expectedSchedule as $command => $expression) {
            $this->assertArrayHasKey($command, $commands);

            $matchingEvents = array_values(array_filter(
                $events,
                fn ($event) => str_contains($event->command, $command),
            ));

            $this->assertCount(1, $matchingEvents, $command.' must be scheduled exactly once.');
            $this->assertSame($expression, $matchingEvents[0]->expression);
            $this->assertTrue($matchingEvents[0]->withoutOverlapping);
        }
    }

    private function createUser(string $role): User
    {
        return User::query()->create([
            'name' => 'Bootstrap '.$role,
            'login' => 'bootstrap-'.$role,
            'role' => $role,
            'password' => 'secret123',
        ]);
    }
}
