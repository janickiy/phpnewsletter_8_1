<?php

namespace Tests\Feature;

use App\DTO\Update\CategoryUpdateData;
use App\Models\Category;
use App\Models\Project;
use App\Models\User;
use App\Repositories\CategoryRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InactiveProjectCategoriesTest extends TestCase
{
    use RefreshDatabase;

    private Project $project;
    private Category $category;
    private Category $defaultCategory;
    private Category $orphanCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $administrator = User::query()->create([
            'name' => 'Administrator', 'login' => 'category-admin',
            'role' => User::ROLE_ADMIN, 'password' => 'test-password',
        ]);
        $this->project = Project::query()->create([
            'name' => 'Switchable project', 'status' => true, 'owner_id' => $administrator->id,
        ]);
        $this->category = Category::query()->create(['project_id' => $this->project->id, 'name' => 'Project category']);
        $this->defaultCategory = Category::query()->create(['project_id' => 0, 'name' => 'Default category']);
        $this->orphanCategory = Category::query()->create(['project_id' => null, 'name' => 'Preserved category']);
        $this->actingAs($administrator);
    }

    public function test_category_table_and_dashboard_are_independent_of_project_status(): void
    {
        foreach ([[true, 3], [false, 3]] as [$active, $count]) {
            $this->project->update(['status' => $active]);

            $response = $this->getJson(route('admin.datatable.category'))
                ->assertOk()->assertJsonPath('recordsTotal', $count);
            $ids = array_map('intval', array_column($response->json('data'), 'id'));
            $this->assertContains($this->defaultCategory->id, $ids);
            $this->assertContains($this->orphanCategory->id, $ids);
            $this->assertContains($this->category->id, $ids);
            $this->get(route('admin.dashboard.index'))->assertOk()
                ->assertViewHas('stats', fn (array $stats) => $stats['categories'] === $count);
        }

        $this->project->update(['status' => true]);
        $this->getJson(route('admin.datatable.category'))->assertOk()->assertJsonPath('recordsTotal', 3);
        $this->assertModelExists($this->category);
    }

    public function test_categories_remain_manageable_after_a_project_is_disabled(): void
    {
        $this->project->update(['status' => false]);

        $this->get(route('admin.category.edit', $this->category->id))->assertOk()
            ->assertDontSee('name="project_id"', false);
        $this->put(route('admin.category.update'), [
            'id' => $this->category->id, 'name' => 'Global readers',
        ])->assertRedirect(route('admin.category.index'))->assertSessionHasNoErrors();
        $this->post(route('admin.category.store'), [
            'name' => 'New global category', 'project_id' => $this->project->id,
        ])->assertRedirect(route('admin.category.index'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('categories', ['name' => 'New global category']);

        $repository = app(CategoryRepository::class);
        $this->assertSame($this->category->id, $repository->find($this->category->id)->id);
        $this->assertTrue($repository->update($this->category->id, new CategoryUpdateData('Renamed globally')));
        $this->assertArrayHasKey($this->category->id, $repository->getOption());
        $this->assertTrue($repository->delete($this->category->id));
        $this->assertDatabaseCount('categories', 3);
    }

    public function test_default_and_preserved_categories_remain_editable_while_other_projects_are_inactive(): void
    {
        $this->project->update(['status' => false]);

        foreach ([$this->defaultCategory, $this->orphanCategory] as $category) {
            $this->get(route('admin.category.edit', $category->id))->assertOk();
            $this->put(route('admin.category.update'), [
                'id' => $category->id, 'name' => 'Renamed '.$category->id,
            ])->assertRedirect(route('admin.category.index'))->assertSessionHasNoErrors()->assertSessionMissing('error');
            $this->assertDatabaseHas('categories', [
                'id' => $category->id, 'name' => 'Renamed '.$category->id,
            ]);
        }
    }
}
