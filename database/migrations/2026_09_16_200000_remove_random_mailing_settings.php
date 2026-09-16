<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const array RETIRED_SETTINGS = [
        'RANDOM_SEND',
        'RENDOM_REPLACEMENT_SUBJECT',
        'RANDOM_REPLACEMENT_BODY',
    ];

    public function up(): void
    {
        DB::table('settings')->whereIn('name', self::RETIRED_SETTINGS)->delete();
    }

    /**
     * Restore former defaults without overwriting any values restored separately.
     */
    public function down(): void
    {
        foreach (self::RETIRED_SETTINGS as $name) {
            if (! DB::table('settings')->where('name', $name)->exists()) {
                DB::table('settings')->insert(['name' => $name, 'value' => '0']);
            }
        }
    }
};
