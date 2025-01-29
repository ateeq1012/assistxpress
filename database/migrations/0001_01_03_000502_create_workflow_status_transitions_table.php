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
        Schema::create('workflow_status_transitions', function (Blueprint $table) {
            $table->id();
            $table->integer('workflow_id');
            $table->integer('status_from_id')->nullable();
            $table->integer('status_to_id');
            $table->integer('transition_type')->default(1)->comment('[0=>"New", 1=>"Issuer", 2=>"issuer Group Users", 3=>"Receiver", 4=>"Receiver Group Users", 5=>"General Users By Role", 6=>"General Users By Group"]');
            $table->integer('role_id')->nullable();
            $table->integer('group_id')->nullable();
            $table->integer('user_id')->nullable();
            $table->integer('created_by');
            $table->timestamp('created_at');

            $table->foreign('workflow_id')->references('id')->on('workflows')->onDelete("NO ACTION");
            $table->foreign('status_from_id')->references('id')->on('statuses')->onDelete("NO ACTION");
            $table->foreign('status_to_id')->references('id')->on('statuses')->onDelete("NO ACTION");
            $table->foreign('role_id')->references('id')->on('roles')->onDelete("NO ACTION");
            $table->foreign('group_id')->references('id')->on('groups')->onDelete("NO ACTION");
            $table->foreign('user_id')->references('id')->on('users')->onDelete("NO ACTION");
            $table->foreign('created_by')->references('id')->on('users')->onDelete("NO ACTION");


        });

        // DB::statement('
        //     ALTER TABLE workflow_status_transitions
        //     ADD CONSTRAINT check_role_group_user
        //     CHECK (
        //         (role_id IS NOT NULL AND group_id IS NULL AND user_id IS NULL) OR
        //         (role_id IS NULL AND group_id IS NOT NULL AND user_id IS NULL) OR
        //         (role_id IS NULL AND group_id IS NULL AND user_id IS NOT NULL)
        //     )
        // ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workflow_status_transitions');
    }
};
