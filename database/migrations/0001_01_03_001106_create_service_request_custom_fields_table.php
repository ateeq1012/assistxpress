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
        Schema::create('service_request_custom_fields', function (Blueprint $table) {
            $table->id();
            $table->integer('service_request_id');
            $table->integer('field_id');
            $table->text('value');
            $table->jsonb('jsonval')->nullable();
            $table->foreign('service_request_id')->references('id')->on('service_requests')->onDelete("NO ACTION");
            $table->foreign('field_id')->references('id')->on('custom_fields')->onDelete("NO ACTION");

            $table->index('service_request_id');
            $table->index('field_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_request_custom_fields');
    }
};
