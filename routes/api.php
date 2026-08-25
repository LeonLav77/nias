<?php

declare(strict_types=1);

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use LeonLav77\NiasAgeVerification\Http\Controllers\AgeVerificationController;

Route::controller(AgeVerificationController::class)->group(static function (Router $route): void {
	$route->post('start', 'start')->name('start');
	$route->get('callback', 'callback')->name('callback');
});
