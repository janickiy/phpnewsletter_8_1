<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Outgoing mail always uses UTF-8.
     */
    public function up(): void
    {
        DB::table('settings')->where('name', 'CHARSET')->delete();
    }

    /**
     * Restore the former default, since the removed custom value is unavailable.
     */
    public function down(): void
    {
        DB::table('settings')->updateOrInsert(
            ['name' => 'CHARSET'],
            ['value' => 'utf-8'],
        );
    }
};
