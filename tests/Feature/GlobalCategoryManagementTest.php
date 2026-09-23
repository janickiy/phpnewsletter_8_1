<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GlobalCategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_creates_a_category_without_a_project_and_legacy_input_cannot_bind_it(): void
    {
        $admin = User::query()->create([
            'name' => 'Administrator', 'login' => 'global-category-admin', 'role' => User::ROLE_ADMIN, 'password' => 'password',
        ]);
        $this->actingAs($admin);

        $this->get(route('admin.category.create'))->assertOk()->assertDontSee('name="project_id"', false);
        foreach ([[], ['project_id' => 999999]] as $index => $legacyInput) {
            $this->post(route('admin.category.store'), ['name' => 'Global '.$index, ...$legacyInput])
                ->assertRedirect(route('admin.category.index'))->assertSessionHasNoErrors()->assertSessionMissing('error');
            $this->assertDatabaseHas('categories', ['name' => 'Global '.$index]);
        }
        $direct = Category::query()->create(['name' => 'Legacy model caller', 'project_id' => 999999]);
        $this->assertFalse(Schema::hasColumn('categories', 'project_id'));
        $this->assertFalse(Schema::hasColumn('categories', 'project_reference_id'));
        $this->assertArrayNotHasKey('project_id', $direct->fresh()->toArray());
        $row = $this->getJson(route('admin.datatable.category'))->assertOk()->json('data.0');
        $this->assertArrayNotHasKey('project', $row);
    }
}
