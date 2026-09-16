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
        Schema::create('ready_sent', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('subscriber_id');
            $table->string('email');

            $table->unsignedInteger('template_id');
            $table->string('template');
            $table->tinyInteger('success');
            $table->text('errorMsg')->nullable();
            $table->tinyInteger('readMail')->nullable();

            $table->unsignedInteger('schedule_id')->nullable();
            $table->unsignedInteger('log_id')->nullable();

            $table->timestamps();

            $table->foreign('subscriber_id')->references('id')->on('subscribers')->onDelete('cascade');
            $table->foreign('template_id')->references('id')->on('templates')->onDelete('cascade');
            $table->foreign('schedule_id')->references('id')->on('schedule')->nullOnDelete();
            $table->foreign('log_id')->references('id')->on('logs')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ready_sent');
    }
};
