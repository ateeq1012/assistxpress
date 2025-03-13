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
		Schema::create('routes', function (Blueprint $table) {
			$table->id();
			$table->string('type'); // e.g., web, api
			$table->string('name')->index()->unique(); // route name
			$table->string('url')->index(); // URI
			$table->string('method'); // GET, POST, etc.
			$table->string('controller'); // Controller@method
			$table->string('middleware')->nullable();
            $table->timestamp('created_at');
            $table->timestamp('updated_at')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('routes');
	}
};