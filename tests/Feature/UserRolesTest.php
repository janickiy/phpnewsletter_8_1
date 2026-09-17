<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_an_administrator_can_manage_global_users(): void
    {
        foreach (['project_admin', 'moderator'] as $role) {
            $user = $this->user($role);
            $this->actingAs($user);

            $this->get(route('admin.users.index'))->assertForbidden();
            $this->get(route('admin.users.create'))->assertForbidden();
            $this->post(route('admin.users.store'), $this->payload())->assertForbidden();
            $this->put(route('admin.users.update'), [...$this->payload(), 'id' => $user->id])->assertForbidden();
            $this->delete(route('admin.users.destroy', $user->id))->assertForbidden();
        }

        $this->assertDatabaseMissing('users', ['login' => 'new-user']);
    }

    public function test_administrator_can_create_exactly_the_three_supported_roles(): void
    {
        $this->actingAs($this->user('admin'));

        foreach (['admin', 'project_admin', 'moderator'] as $role) {
            $this->post(route('admin.users.store'), [
                ...$this->payload(),
                'login' => 'new-'.$role,
                'role' => $role,
            ])->assertRedirect(route('admin.users.index'));

            $this->assertDatabaseHas('users', ['login' => 'new-'.$role, 'role' => $role]);
        }

        foreach (['editor', 'owner', 'superadmin'] as $role) {
            $this->post(route('admin.users.store'), [...$this->payload(), 'role' => $role])
                ->assertSessionHasErrors('role');
        }

        $this->assertDatabaseMissing('users', ['login' => 'new-user']);
    }

    public function test_an_administrator_cannot_demote_their_own_account_with_a_forged_request(): void
    {
        $admin = $this->user('admin');
        $this->actingAs($admin);

        $this->put(route('admin.users.update'), [
            ...$this->payload(),
            'id' => $admin->id,
            'login' => $admin->login,
            'role' => 'moderator',
        ])->assertSessionHasErrors('role');

        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_user_edit_rejects_unknown_role_and_can_change_another_users_valid_role(): void
    {
        $this->actingAs($this->user('admin'));
        $user = $this->user('moderator');
        $payload = [...$this->payload(), 'id' => $user->id, 'login' => $user->login];

        $this->put(route('admin.users.update'), [...$payload, 'role' => 'editor'])
            ->assertSessionHasErrors('role');
        $this->assertSame('moderator', $user->fresh()->role);

        $this->put(route('admin.users.update'), [...$payload, 'role' => 'project_admin'])
            ->assertRedirect(route('admin.users.index'));
        $this->assertSame('project_admin', $user->fresh()->role);
    }

    private function user(string $role): User
    {
        return User::query()->create([
            'name' => ucfirst($role),
            'login' => $role.'-'.User::query()->count(),
            'role' => $role,
            'password' => 'secret123',
        ]);
    }

    private function payload(): array
    {
        return [
            'name' => 'New user',
            'login' => 'new-user',
            'password' => 'secret123',
            'password_again' => 'secret123',
            'role' => 'moderator',
            'description' => 'Managed account',
        ];
    }
}
