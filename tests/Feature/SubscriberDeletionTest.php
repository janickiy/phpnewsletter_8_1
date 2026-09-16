<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\SubscribersController;
use App\Models\Subscribers;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SubscriberDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_destroy_route_contract_and_controller_deletion_are_aligned(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin',
            'login' => 'admin',
            'role' => User::ROLE_ADMIN,
            'password' => bcrypt('secret123'),
        ]);

        $subscriber = Subscribers::query()->create([
            'name' => 'Delete Me',
            'email' => 'delete-me@example.com',
            'active' => 1,
            'token' => str_repeat('b', 32),
            'timeSent' => now(),
        ]);

        $route = Route::getRoutes()->match(
            Request::create('/subscribers/destroy/' . $subscriber->id, 'DELETE')
        );

        $this->assertSame('admin.subscribers.destroy', $route->getName());
        $this->actingAs($admin);

        app(SubscribersController::class)->destroy($subscriber->id);

        $this->assertDatabaseMissing('subscribers', ['id' => $subscriber->id]);
    }
}
