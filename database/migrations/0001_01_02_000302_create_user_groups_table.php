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
        Schema::create('user_groups', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable();
            $table->integer('group_id')->nullable();
            $table->integer('created_by')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete("CASCADE");
            $table->foreign('group_id')->references('id')->on('groups')->onDelete("CASCADE");
            $table->foreign('created_by')->references('id')->on('users')->onDelete("NO ACTION");
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_groups', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['group_id']);
            $table->dropForeign(['created_by']);
        });
        
        Schema::dropIfExists('user_groups');
    }
};
