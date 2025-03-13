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
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->integer('parent_id')->nullable();
            $table->boolean('enabled')->default(true);
            $table->integer('created_by');
            $table->timestamp('created_at');
            $table->integer('updated_by')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->foreign('parent_id')->references('id')->on('groups')->onDelete("SET NULL");
            $table->foreign('created_by')->references('id')->on('users')->onDelete("NO ACTION");
            $table->foreign('updated_by')->references('id')->on('users')->onDelete("NO ACTION");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('groups', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);
        });
        Schema::dropIfExists('groups');
    }
};
