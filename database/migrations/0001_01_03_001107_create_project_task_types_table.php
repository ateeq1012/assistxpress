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
        Schema::create('project_task_types', function (Blueprint $table) {
            $table->id();
            $table->integer('project_id');
            $table->integer('task_type_id');

            $table->foreign('project_id')->references('id')->on('projects')->onDelete("NO ACTION");
            $table->foreign('task_type_id')->references('id')->on('task_types')->onDelete("NO ACTION");

            $table->index('project_id');
            $table->index('task_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_task_types');
    }
};
