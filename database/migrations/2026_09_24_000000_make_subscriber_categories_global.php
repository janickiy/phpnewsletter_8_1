<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Keep identifiers and every subscriber/schedule association intact.
        if (Schema::hasColumn('categories', 'project_id')) {
            DB::table('categories')->whereNotNull('project_id')->update(['project_id' => null]);
        }
    }

    public function down(): void
    {
        // Former project ownership cannot be inferred after categories become shared.
        // Preserve categories and subscriptions when rolling back the application.
    }
};
