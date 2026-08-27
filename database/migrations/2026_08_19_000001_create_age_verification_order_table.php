<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
	public function up(): void
	{
		Schema::create('age_verification_order', function (Blueprint $table): void {
			$table->foreignUuid('age_verification_id')
				->constrained('age_verifications')
				->cascadeOnDelete();

			$table->foreignUuid('order_id')->index();

			$table->timestamps();
			$table->unique(['age_verification_id', 'order_id']);
		});
	}

	public function down(): void
	{
		Schema::dropIfExists('age_verification_order');
	}
};
