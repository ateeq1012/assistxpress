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
        Schema::create('task_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('task_id');
            $table->integer('project_id'); // without relation
            $table->integer('task_type_id'); // without relation
            $table->string('field_name');
            $table->string('old_value')->nullable();
            $table->string('new_value')->nullable();
            $table->integer('created_by');
            $table->timestamp('created_at');
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete("CASCADE");
            $table->foreign('project_id')->references('id')->on('projects')->onDelete("CASCADE");
            $table->foreign('task_type_id')->references('id')->on('task_types')->onDelete("CASCADE");
            $table->foreign('created_by')->references('id')->on('users')->onDelete("NO ACTION");

            $table->index('task_id');
            $table->index('project_id');
            $table->index('task_type_id');
            $table->index('created_by');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_audit_logs');
    }
};
