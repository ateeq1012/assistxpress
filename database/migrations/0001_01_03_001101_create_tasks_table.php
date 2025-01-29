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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->integer('project_id');
            $table->text('subject');
            $table->text('description')->nullable();

            $table->integer('task_type_id');
            $table->integer('status_id');
            $table->integer('priority_id')->nullable();
            $table->integer('creator_group_id')->nullable();
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('executor_id')->nullable();
            $table->integer('executor_group_id')->nullable();
            $table->integer('sla_rule_id')->nullable();
            $table->integer('time_spent')->default(0);

            $table->timestamp('planned_start')->nullable();
            $table->timestamp('planned_end')->nullable();
            $table->timestamp('actual_execution_start')->nullable();
            $table->timestamp('actual_execution_end')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at')->nullable();

            $table->foreign('project_id')->references('id')->on('projects')->onDelete("NO ACTION");
            $table->foreign('task_type_id')->references('id')->on('task_types')->onDelete("NO ACTION");
            $table->foreign('status_id')->references('id')->on('statuses')->onDelete("NO ACTION");
            $table->foreign('priority_id')->references('id')->on('task_priorities')->onDelete("NO ACTION");
            $table->foreign('creator_group_id')->references('id')->on('groups')->onDelete("NO ACTION");
            $table->foreign('created_by')->references('id')->on('users')->onDelete("NO ACTION");
            $table->foreign('updated_by')->references('id')->on('users')->onDelete("NO ACTION");
            $table->foreign('executor_id')->references('id')->on('users')->onDelete("NO ACTION");
            $table->foreign('executor_group_id')->references('id')->on('groups')->onDelete("NO ACTION");
            $table->foreign('sla_rule_id')->references('id')->on('sla_rules')->onDelete("NO ACTION");

            $table->index('project_id');
            $table->index('task_type_id');
            $table->index('status_id');
            $table->index('priority_id');
            $table->index('created_by');
            $table->index('updated_by');
            $table->index('executor_id');
            $table->index('executor_group_id');
            $table->index('creator_group_id');
            $table->index('sla_rule_id');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
