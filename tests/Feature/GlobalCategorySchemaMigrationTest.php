<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Project;
use App\Models\Schedule;
use App\Models\Templates;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GlobalCategorySchemaMigrationTest extends TestCase
{
    // MySQL schema changes commit implicitly, so these tests must not use transactions.
    use DatabaseMigrations;

    public function test_fresh_install_has_no_category_project_columns_and_upgrade_migrations_are_safe_to_repeat(): void
    {
        $category = Category::query()->create(['name' => 'Global category']);

        $this->assertGlobalCategorySchema();

        $clearBindings = require database_path('migrations/2026_09_24_000000_make_subscriber_categories_global.php');
        $dropColumns = require database_path('migrations/2026_09_24_010000_drop_category_project_columns.php');
        $clearBindings->up();
        $dropColumns->up();
        $clearBindings->up();
        $dropColumns->up();

        $this->assertGlobalCategorySchema();
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'Global category']);
        $this->assertDatabaseCount('categories', 1);
    }

    public function test_upgrade_drops_legacy_foreign_key_and_columns_without_changing_categories_or_links(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->unsignedInteger('project_id')->nullable()->index();
            $table->unsignedInteger('project_reference_id')->nullable()->storedAs('nullif(project_id, 0)');
            $table->foreign('project_reference_id')->references('id')->on('projects')->restrictOnDelete();
        });

        $projectId = $this->testProjectId();
        $subscriber = $this->subscriberFixture([
            'email' => 'global-migration@example.test', 'active' => 1, 'token' => str_repeat('g', 32),
        ], [$projectId]);
        $template = Templates::query()->create([
            'project_id' => $projectId, 'name' => 'Newsletter', 'body' => 'Hello', 'prior' => 0,
        ]);
        $schedule = Schedule::query()->create([
            'project_id' => $projectId, 'template_id' => $template->id, 'event_name' => 'Newsletter',
            'event_start' => now(), 'event_end' => now()->addDay(),
        ]);
        $categoryIds = [];
        foreach ([Project::DEFAULT_ID, $projectId, null] as $index => $binding) {
            $categoryId = DB::table('categories')->insertGetId([
                'project_id' => $binding, 'name' => 'Preserved category '.$index,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            $categoryIds[] = $categoryId;
            DB::table('subscriptions')->insert(['subscriber_id' => $subscriber->id, 'category_id' => $categoryId]);
            DB::table('schedule_category')->insert(['schedule_id' => $schedule->id, 'category_id' => $categoryId]);
        }
        $originalCategories = DB::table('categories')->orderBy('id')->get(['id', 'name', 'created_at', 'updated_at'])->toArray();
        $originalSubscriptions = DB::table('subscriptions')->orderBy('category_id')->get()->toArray();
        $originalScheduleCategories = DB::table('schedule_category')->orderBy('category_id')->get()->toArray();

        $this->assertCount(1, Schema::getForeignKeys('categories'));
        $this->assertSame(2, DB::table('categories')->whereNotNull('project_id')->count());

        $migration = require database_path('migrations/2026_09_24_010000_drop_category_project_columns.php');
        $migration->up();
        $migration->up();

        $this->assertGlobalCategorySchema();
        $this->assertEquals($originalCategories, DB::table('categories')->orderBy('id')->get()->toArray());
        $this->assertEquals($originalSubscriptions, DB::table('subscriptions')->orderBy('category_id')->get()->toArray());
        $this->assertEquals($originalScheduleCategories, DB::table('schedule_category')->orderBy('category_id')->get()->toArray());
        $this->assertSame($categoryIds, Category::query()->orderBy('id')->pluck('id')->all());
    }

    private function assertGlobalCategorySchema(): void
    {
        $this->assertFalse(Schema::hasColumn('categories', 'project_id'));
        $this->assertFalse(Schema::hasColumn('categories', 'project_reference_id'));
        $this->assertEmpty(Schema::getForeignKeys('categories'));
        foreach (Schema::getIndexes('categories') as $index) {
            $this->assertNotContains('project_id', $index['columns']);
            $this->assertNotContains('project_reference_id', $index['columns']);
        }
    }
}
