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
        Schema::create('user_role_routes', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable();
            $table->integer('role_id')->nullable();
            $table->integer('route_id');
            $table->boolean('is_allowed')->nullable(); // if user specific assignment, this flag will decide whether user is allowed or blocked for the action.
            $table->integer('created_by');
            $table->timestamp('created_at');
            $table->foreign('user_id')->references('id')->on('users')->onDelete("CASCADE");
            $table->foreign('role_id')->references('id')->on('roles')->onDelete("CASCADE");
            $table->foreign('route_id')->references('id')->on('routes')->onDelete("CASCADE");
            $table->foreign('created_by')->references('id')->on('users')->onDelete("NO ACTION");
        });
        
        DB::statement('ALTER TABLE user_role_routes ADD CONSTRAINT chk_user_role CHECK (user_id IS NOT NULL OR role_id IS NOT NULL)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE user_role_routes DROP CONSTRAINT IF EXISTS chk_user_role');

        Schema::table('user_role_routes', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['role_id']);
            $table->dropForeign(['route_id']);
            $table->dropForeign(['created_by']);
        });
        Schema::dropIfExists('user_role_routes');
    }
};
