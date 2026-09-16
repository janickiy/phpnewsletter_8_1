<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_log_in_and_log_out(): void
    {
        $admin = $this->createAdministrator();

        $this->post(route('login.submit'), [
            'login' => $admin->login,
            'password' => 'secret123',
        ])->assertRedirect(route('admin.dashboard.index'));

        $this->assertAuthenticatedAs($admin);
        $this->get(route('admin.dashboard.index'))->assertOk();

        $this->get(route('logout'))->assertRedirect(route('login'));

        $this->assertGuest();
        $this->get(route('admin.dashboard.index'))->assertRedirect(route('login'));
    }

    public function test_invalid_password_does_not_authenticate_the_administrator(): void
    {
        $admin = $this->createAdministrator();

        $this->from(route('login'))->post(route('login.submit'), [
            'login' => $admin->login,
            'password' => 'incorrect-password',
        ])->assertRedirect(route('login'))->assertSessionHasErrors('login');

        $this->assertGuest();
    }

    public function test_authenticated_users_are_redirected_from_login_to_the_dashboard(): void
    {
        $this->actingAs($this->createAdministrator())
            ->get(route('login'))
            ->assertRedirect(route('admin.dashboard.index'));
    }

    public function test_password_whitespace_is_preserved_by_request_normalization(): void
    {
        $admin = $this->createAdministrator();
        $admin->update(['password' => ' secret123 ']);

        $this->post(route('login.submit'), [
            'login' => $admin->login,
            'password' => ' secret123 ',
        ])->assertRedirect(route('admin.dashboard.index'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_unauthenticated_api_requests_return_json_instead_of_redirecting(): void
    {
        $this->getJson('/api/user')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_sanctum_personal_access_token_authenticates_the_api(): void
    {
        $admin = $this->createAdministrator();
        $token = $admin->createToken('upgrade-test')->plainTextToken;

        $this->withToken($token)->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('id', $admin->id)
            ->assertJsonMissingPath('password');
    }

    public function test_login_without_csrf_token_is_rejected_outside_the_test_bypass(): void
    {
        $this->app['env'] = 'local';

        $this->post(route('login.submit'), [
            'login' => 'upgrade-admin',
            'password' => 'secret123',
        ])->assertStatus(419);

        $this->assertGuest();
    }

    public function test_login_with_matching_csrf_token_is_accepted(): void
    {
        $admin = $this->createAdministrator();
        $this->app['env'] = 'local';

        $this->withSession(['_token' => 'upgrade-csrf-token'])
            ->post(route('login.submit'), [
                '_token' => 'upgrade-csrf-token',
                'login' => $admin->login,
                'password' => 'secret123',
            ])->assertRedirect(route('admin.dashboard.index'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_embedded_subscription_endpoint_keeps_its_csrf_exemption(): void
    {
        $this->app['env'] = 'local';

        $this->postJson(route('frontend.addsub'), ['email' => 'invalid-email'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_same_origin_login_is_accepted_by_laravel_request_forgery_protection(): void
    {
        $admin = $this->createAdministrator();
        $this->app['env'] = 'local';

        $this->withHeader('Sec-Fetch-Site', 'same-origin')
            ->post(route('login.submit'), [
                'login' => $admin->login,
                'password' => 'secret123',
            ])->assertRedirect(route('admin.dashboard.index'));

        $this->assertAuthenticatedAs($admin);
    }

    private function createAdministrator(): User
    {
        return User::query()->create([
            'name' => 'Upgrade administrator',
            'login' => 'upgrade-admin',
            'role' => User::ROLE_ADMIN,
            'password' => 'secret123',
        ]);
    }
}
