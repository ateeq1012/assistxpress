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
        Schema::create('service_domain_groups', function (Blueprint $table) {
            $table->id();
            $table->integer('group_id')->nullable();
            $table->integer('service_domain_id')->nullable();
            $table->integer('created_by')->nullable();
            $table->foreign('group_id')->references('id')->on('groups')->onDelete("CASCADE");
            $table->foreign('service_domain_id')->references('id')->on('service_domains')->onDelete("CASCADE");
            $table->foreign('created_by')->references('id')->on('users')->onDelete("NO ACTION");
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_domain_groups');
    }
};
