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
        Schema::create('redirect', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('project_id')->index();
            // The virtual default project (0) has no row; real projects retain referential integrity.
            $table->unsignedInteger('project_reference_id')->nullable()->storedAs('nullif(project_id, 0)');
            $table->foreign('project_reference_id')->references('id')->on('projects')->restrictOnDelete();
            $table->string('url');
            $table->string('email');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('redirect');
    }
};
