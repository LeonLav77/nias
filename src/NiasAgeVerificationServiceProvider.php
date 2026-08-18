<?php

declare(strict_types=1);

namespace Vendor\NiasAgeVerification;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class NiasAgeVerificationServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		$this->mergeConfigFrom(__DIR__ . '/../config/nias-age-verification.php', 'nias-age-verification');
	}

	public function boot(): void
	{
		$this->publishes([
			__DIR__ . '/../config/nias-age-verification.php' => config_path('nias-age-verification.php'),
		], 'nias-age-verification-config');

		Route::prefix('api/age-verification')
			->name('nias-age-verification.')
			->middleware('api')
			->group(__DIR__ . '/../routes/api.php');
	}
}
