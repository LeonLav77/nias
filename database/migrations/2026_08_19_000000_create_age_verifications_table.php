<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
	public function up(): void
	{
		Schema::create('age_verifications', function (Blueprint $table): void {
			$table->uuid('id')->primary();
			$table->text('id_token');
			$table->char('token_hash', 64)->unique();
			$table->boolean('is_adult');

			$table->timestamp('verified_at');
			$table->timestamp('expires_at');

			$table->timestamps();
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('age_verifications');
	}
};
