<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RedirectTemplateSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_fresh_install_accepts_unknown_or_deleted_templates_without_a_project_binding(): void
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

        $row = [
            'url' => 'https://example.test/tracked', 'email' => 'reader@example.test',
            'created_at' => '2026-09-01 12:34:56', 'updated_at' => '2026-09-01 12:34:56',
        ];
        $unknownId = DB::table('redirect')->insertGetId($row);
        $snapshot = ['template_id' => 2147483647, 'template' => 'Previously deleted newsletter'];
        $deletedId = DB::table('redirect')->insertGetId($row + $snapshot);

        $this->assertDatabaseHas('redirect', ['id' => $unknownId, 'template_id' => null, 'template' => null] + $row);
        $this->assertDatabaseHas('redirect', ['id' => $deletedId] + $row + $snapshot);
        $this->assertDatabaseCount('redirect', 2);
    }
}
