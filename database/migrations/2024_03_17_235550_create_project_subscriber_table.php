<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_subscriber', function (Blueprint $table) {
            $table->unsignedInteger('project_id');
            $table->unsignedBigInteger('subscriber_id');
            $table->timestamps();
            $table->primary(['project_id', 'subscriber_id']);
            $table->index('subscriber_id');
            $table->foreign('project_id')->references('id')->on('projects')->cascadeOnDelete();
            $table->foreign('subscriber_id')->references('id')->on('subscribers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_subscriber');
    }
};
