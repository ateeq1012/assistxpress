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
        Schema::create('task_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->jsonb('settings')->nullable();
            $table->jsonb('approval_settings')->nullable();
            $table->integer('workflow_id');
            // $table->boolean('is_planned')->default(false);
            $table->char('color', 7)->default('#d1dade');
            $table->boolean('enabled')->default(true);
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at')->nullable();
            $table->foreign('workflow_id')->references('id')->on('workflows')->onDelete("NO ACTION");
            $table->foreign('created_by')->references('id')->on('users')->onDelete("NO ACTION");
            $table->foreign('updated_by')->references('id')->on('users')->onDelete("NO ACTION");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_types');
    }
};
