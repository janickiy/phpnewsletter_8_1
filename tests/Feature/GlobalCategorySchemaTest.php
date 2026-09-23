<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GlobalCategorySchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_install_creates_categories_without_a_project_binding(): void
    {
        $category = Category::query()->create(['name' => 'Global category']);

        $this->assertFalse(Schema::hasColumn('categories', 'project_id'));
        $this->assertFalse(Schema::hasColumn('categories', 'project_reference_id'));
        $this->assertEmpty(Schema::getForeignKeys('categories'));
        foreach (Schema::getIndexes('categories') as $index) {
            $this->assertNotContains('project_id', $index['columns']);
            $this->assertNotContains('project_reference_id', $index['columns']);
        }
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Global category']);
    }
}
