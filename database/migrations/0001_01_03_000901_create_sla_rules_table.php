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
        Schema::create('sla_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description');
            $table->char('color', 7)->default('#d1dade');
            $table->integer('order')->default(0);
            $table->jsonb('settings');
            $table->jsonb('qb_rules');
            $table->text('query');
            $table->text('logs')->nullable();
            $table->timestamp('last_run_ts')->nullable();
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete("NO ACTION");
            $table->foreign('updated_by')->references('id')->on('users')->onDelete("NO ACTION");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sla_rules');
    }
};
