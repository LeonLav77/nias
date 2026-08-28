<?php

declare(strict_types=1);

namespace LeonLav77\NiasAgeVerification;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use LeonLav77\NiasAgeVerification\Http\Middleware\RequireAgeVerification;

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

		$this->publishes([
			__DIR__ . '/../database/migrations' => database_path('migrations'),
		], 'nias-age-verification-migrations');

		$this->loadTranslationsFrom(__DIR__ . '/../lang', 'nias-age-verification');

		$this->publishes([
			__DIR__ . '/../lang' => $this->app->langPath('vendor/nias-age-verification'),
		], 'nias-age-verification-translations');

		Route::aliasMiddleware('nias.age-verification', RequireAgeVerification::class);

		Route::prefix('api/age-verification')
			->name('nias-age-verification.')
			->middleware('api')
			->group(__DIR__ . '/../routes/api.php');
	}
}
