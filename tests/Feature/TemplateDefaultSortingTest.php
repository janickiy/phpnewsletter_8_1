<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateDefaultSortingTest extends TestCase
{
    use RefreshDatabase;

    public function test_templates_are_sorted_by_newest_id_by_default(): void
    {
        $admin = User::query()->create([
            'name' => 'Template sorting admin',
            'login' => 'template-sorting-admin',
            'description' => null,
            'role' => User::ROLE_ADMIN,
            'password' => 'password',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.templates.index'))
            ->assertOk()
            ->assertSee("aaSorting: [[1, 'desc']]", false);
    }
}
