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
        Schema::create('task_attachements', function (Blueprint $table) {
            $table->id();
            $table->text('name');
            $table->text('url');
            $table->integer('task_id');
            $table->text('field_id');
            $table->integer('created_by');
            $table->timestamp('created_at');

            $table->foreign('created_by')->references('id')->on('users')->onDelete("NO ACTION");
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete("CASCADE");

            $table->index('created_by');
            $table->index('task_id');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_attachements');
    }
};
