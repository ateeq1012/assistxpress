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
        Schema::create('service_request_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->integer('service_request_id');
            $table->string('field_name');
            $table->integer('field_type')->comment('[1=>"System Field", 2=>"Custom Field", 3=>"Other"]');
            $table->string('old_value')->nullable();
            $table->string('new_value')->nullable();
            $table->string('file_path')->nullable();
            $table->integer('created_by');
            $table->timestamp('created_at');
            $table->foreign('service_request_id')->references('id')->on('service_requests')->onDelete("CASCADE");
            $table->foreign('created_by')->references('id')->on('users')->onDelete("NO ACTION");

            $table->index('service_request_id');
            $table->index('created_by');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_request_audit_logs');
    }
};
