<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Logs;
use App\Models\Templates;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VirtualProjectMailingTest extends TestCase
{
    use RefreshDatabase;

    public function test_project_administrator_can_start_default_project_mailing_without_a_stored_project(): void
    {
        $manager = User::query()->create([
            'name' => 'Manager', 'login' => 'virtual-mail-manager', 'role' => User::ROLE_PROJECT_ADMIN, 'password' => 'password',
        ]);
        $template = Templates::query()->create([
            'project_id' => 0, 'name' => 'Common newsletter', 'body' => '<p>Newsletter</p>', 'prior' => 0,
        ]);
        $category = Category::query()->create(['project_id' => 0, 'name' => 'Common readers']);
        $payload = ['action' => 'start_mailing', 'templateId' => [$template->id], 'categoryId' => [$category->id]];

        $response = $this->actingAs($manager)->postJson(route('admin.ajax.action'), $payload)
            ->assertOk()->assertJsonPath('result', true);
        $this->assertDatabaseHas('logs', ['id' => $response->json('logId'), 'user_id' => $manager->id]);
        $this->assertDatabaseCount('projects', 0);

        $moderator = User::query()->create([
            'name' => 'Moderator', 'login' => 'virtual-mail-moderator', 'role' => User::ROLE_MODERATOR, 'password' => 'password',
        ]);
        $this->actingAs($moderator)->postJson(route('admin.ajax.action'), $payload)->assertForbidden();
        $this->assertSame(1, Logs::query()->count());
    }
}
