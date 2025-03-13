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
        Schema::create('service_domain_users', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id')->nullable();
            $table->integer('service_domain_id')->nullable();
            $table->integer('created_by')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete("CASCADE");
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
        Schema::dropIfExists('service_domain_users');
    }
};
