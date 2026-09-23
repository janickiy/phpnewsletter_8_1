<?php

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RedirectTemplateMigrationTest extends TestCase
{
    // MySQL schema changes commit implicitly, so these tests must not use transactions.
    use DatabaseMigrations;

    public function test_fresh_install_has_optional_template_snapshots_and_repeating_upgrade_preserves_them(): void
    {
        $this->assertTemplateSchema();
        $id = DB::table('redirect')->insertGetId($this->legacyRow() + [
            'template_id' => 2147483647,
            'template' => 'Previously deleted newsletter',
        ]);
        $before = DB::table('redirect')->where('id', $id)->first();

        $migration = $this->migration();
        $migration->up();
        $migration->up();

        $this->assertTemplateSchema();
        $this->assertEquals($before, DB::table('redirect')->where('id', $id)->first());
        $this->assertDatabaseCount('redirect', 1);
    }

    public function test_upgrade_preserves_legacy_clicks_and_leaves_unknown_template_fields_null(): void
    {
        Schema::table('redirect', function (Blueprint $table): void {
            $table->dropColumn(['template_id', 'template']);
            $table->unsignedInteger('project_id')->index();
            $table->unsignedInteger('project_reference_id')->nullable()->storedAs('nullif(project_id, 0)');
            $table->foreign('project_reference_id')->references('id')->on('projects')->restrictOnDelete();
        });
        $projectId = $this->testProjectId();
        $firstId = DB::table('redirect')->insertGetId($this->legacyRow() + ['project_id' => Project::DEFAULT_ID]);
        $secondId = DB::table('redirect')->insertGetId(array_replace($this->legacyRow(), [
            'project_id' => $projectId, 'email' => 'stored-project@example.test',
        ]));
        $before = DB::table('redirect')->orderBy('id')
            ->get(['id', 'url', 'email', 'created_at', 'updated_at'])->map(fn ($row) => (array) $row)->all();

        $migration = $this->migration();
        $migration->up();
        $migration->up();

        $this->assertTemplateSchema();
        $this->assertDatabaseCount('redirect', 2);
        foreach ($before as $row) {
            $this->assertEquals($row + ['template_id' => null, 'template' => null],
                (array) DB::table('redirect')->where('id', $row['id'])->first());
        }
        $this->assertSame([$firstId, $secondId], DB::table('redirect')->orderBy('id')->pluck('id')->all());
    }

    public function test_rollback_does_not_destroy_recorded_template_snapshots_or_recreate_unknown_project_bindings(): void
    {
        $id = DB::table('redirect')->insertGetId($this->legacyRow() + [
            'template_id' => 2147483647, 'template' => 'Preserved click',
        ]);
        $before = DB::table('redirect')->where('id', $id)->first();

        $migration = $this->migration();
        $migration->down();

        $this->assertTemplateSchema();
        $this->assertDatabaseCount('redirect', 1);
        $this->assertEquals($before, DB::table('redirect')->where('id', $id)->first());
    }

    private function assertTemplateSchema(): void
    {
        $this->assertTrue(Schema::hasColumns('redirect', ['template_id', 'template']));
        $this->assertFalse(Schema::hasColumn('redirect', 'project_id'));
        $this->assertFalse(Schema::hasColumn('redirect', 'project_reference_id'));
        $columns = collect(Schema::getColumns('redirect'))->keyBy('name');
        $this->assertTrue($columns['template_id']['nullable']);
        $this->assertTrue($columns['template']['nullable']);
        $this->assertTrue(collect(Schema::getIndexes('redirect'))->contains(
            fn (array $index): bool => $index['columns'] === ['template_id']
        ));
        $this->assertEmpty(Schema::getForeignKeys('redirect'));
    }

    private function legacyRow(): array
    {
        return [
            'url' => 'https://example.test/legacy-migration',
            'email' => 'legacy@example.test',
            'created_at' => '2026-09-01 12:34:56',
            'updated_at' => '2026-09-01 12:34:56',
        ];
    }

    private function migration(): \Illuminate\Database\Migrations\Migration
    {
        return require database_path('migrations/2026_09_24_020000_add_template_to_redirect_table.php');
    }
}
