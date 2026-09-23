<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Historical template identifiers and names survive template deletion.
        // Older clicks have no reliable template attribution and remain NULL.
        if (!Schema::hasColumn('redirect', 'template_id')) {
            Schema::table('redirect', function (Blueprint $table): void {
                $table->unsignedInteger('template_id')->nullable()->after('url');
            });
        }
        if (!Schema::hasIndex('redirect', ['template_id'])) {
            Schema::table('redirect', function (Blueprint $table): void {
                $table->index('template_id');
            });
        }
        if (!Schema::hasColumn('redirect', 'template')) {
            Schema::table('redirect', function (Blueprint $table): void {
                $table->string('template')->nullable()->after('template_id');
            });
        }

        $projectColumns = ['project_reference_id', 'project_id'];
        foreach (Schema::getForeignKeys('redirect') as $foreignKey) {
            if (array_intersect($projectColumns, $foreignKey['columns'])) {
                Schema::table('redirect', function (Blueprint $table) use ($foreignKey): void {
                    $table->dropForeign($foreignKey['name']);
                });
            }
        }
        foreach ($projectColumns as $column) {
            if (Schema::hasColumn('redirect', $column)) {
                Schema::table('redirect', function (Blueprint $table) use ($column): void {
                    $table->dropColumn($column);
                });
            }
        }
    }

    public function down(): void
    {
        // Preserve click history: former project ownership cannot be reconstructed.
    }
};
