<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add indexes used by the large subscribers DataTable.
     */
    public function up(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            $table->index('name', 'idx_subscribers_name');
            $table->index('created_at', 'idx_subscribers_created_at');
        });
    }

    /**
     * Remove subscribers DataTable performance indexes.
     */
    public function down(): void
    {
        Schema::table('subscribers', function (Blueprint $table) {
            $table->dropIndex('idx_subscribers_name');
            $table->dropIndex('idx_subscribers_created_at');
        });
    }
};
