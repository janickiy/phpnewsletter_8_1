<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('subscribers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name', 100)->nullable();
            $table->string('email')->unique();
            $table->tinyInteger('active')->default(1);
            $table->string('token', 32);
            $table->timestamp('timeSent')->nullable();
            $table->timestamps();
            $table->index(['active', 'id'], 'idx_subscribers_active_id');
            $table->index('token', 'idx_subscribers_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscribers');
    }
};
