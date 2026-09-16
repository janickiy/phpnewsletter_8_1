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
        Schema::create('smtp', function (Blueprint $table) {
            $table->increments('id');
            $table->string('host');
            $table->string('username');
            $table->string('email');
            $table->string('password')->nullable();
            $table->integer('port');
            $table->string('authentication');
            $table->string('secure');
            $table->integer('timeout');
            $table->tinyInteger('active')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('smtp');
    }
};
