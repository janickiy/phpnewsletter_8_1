<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManualMailingRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_mailing_request_does_not_expire_while_delays_are_applied(): void
    {
        $admin = User::query()->create([
            'name' => 'Mailing admin',
            'login' => 'mailing-admin',
            'description' => null,
            'role' => User::ROLE_ADMIN,
            'password' => 'password',
        ]);

        $response = $this->actingAs($admin)
            ->get(route('admin.templates.index'));

        $response
            ->assertOk()
            ->assertSee('timeout: 0,', false)
            ->assertDontSee('timeout: 10000,', false);
    }
}
