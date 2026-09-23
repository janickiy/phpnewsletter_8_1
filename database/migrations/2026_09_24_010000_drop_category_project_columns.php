<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove the generated dependency before its source column. Existing category
        // records and their subscriber/schedule junctions do not change.
        $columns = ['project_reference_id', 'project_id'];

        foreach (Schema::getForeignKeys('categories') as $foreignKey) {
            if (array_intersect($columns, $foreignKey['columns'])) {
                Schema::table('categories', function (Blueprint $table) use ($foreignKey): void {
                    $table->dropForeign($foreignKey['name']);
                });
            }
        }

        foreach ($columns as $column) {
            if (Schema::hasColumn('categories', $column)) {
                Schema::table('categories', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }
    }

    public function down(): void
    {
        // Categories remain global; obsolete project ownership cannot be restored.
    }
};
