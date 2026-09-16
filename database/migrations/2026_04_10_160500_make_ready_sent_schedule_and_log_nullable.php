<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ready_sent', function (Blueprint $table) {
            $table->dropForeign(['schedule_id']);
            $table->dropForeign(['log_id']);
        });

        Schema::table('ready_sent', function (Blueprint $table) {
            $table->unsignedInteger('schedule_id')->nullable()->change();
            $table->unsignedInteger('log_id')->nullable()->change();
        });

        Schema::table('ready_sent', function (Blueprint $table) {
            $table->foreign('schedule_id')->references('id')->on('schedule')->nullOnDelete();
            $table->foreign('log_id')->references('id')->on('logs')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ready_sent', function (Blueprint $table) {
            $table->dropForeign(['schedule_id']);
            $table->dropForeign(['log_id']);
        });

        Schema::table('ready_sent', function (Blueprint $table) {
            $table->unsignedInteger('schedule_id')->nullable(false)->change();
            $table->unsignedInteger('log_id')->nullable(false)->change();
        });

        Schema::table('ready_sent', function (Blueprint $table) {
            $table->foreign('schedule_id')->references('id')->on('schedule')->onDelete('cascade');
            $table->foreign('log_id')->references('id')->on('logs')->onDelete('cascade');
        });
    }
};
