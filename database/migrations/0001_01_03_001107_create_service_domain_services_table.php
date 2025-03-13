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
        Schema::create('service_domain_services', function (Blueprint $table) {
            $table->id();
            $table->integer('service_domain_id');
            $table->integer('service_id');

            $table->foreign('service_domain_id')->references('id')->on('service_domains')->onDelete("NO ACTION");
            $table->foreign('service_id')->references('id')->on('services')->onDelete("NO ACTION");

            $table->index('service_domain_id');
            $table->index('service_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_domain_services');
    }
};
