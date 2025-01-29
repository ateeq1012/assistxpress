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
        Schema::create('task_custom_fields', function (Blueprint $table) {
            $table->id();
            $table->integer('task_id');
            $table->integer('field_id');
            $table->text('value');
            $table->jsonb('jsonval')->nullable();
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete("NO ACTION");
            $table->foreign('field_id')->references('id')->on('custom_fields')->onDelete("NO ACTION");

            $table->index('task_id');
            $table->index('field_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_custom_fields');
    }
};
