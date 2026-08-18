<?php

declare(strict_types=1);

namespace Vendor\NiasAgeVerification\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Vendor\NiasAgeVerification\AgeVerificationService;
use Vendor\NiasAgeVerification\Contracts\IsAgeRestricted;
use Vendor\NiasAgeVerification\Http\Requests\CallbackRequest;

class AgeVerificationController
{
	public function __construct(
		protected AgeVerificationService $service,
	) {
	}

	// TODO: ADD VALIDATION
	public function start(Request $request): JsonResponse
	{
		/** @var class-string<IsAgeRestricted> $model */
		$model = config('nias-age-verification.product_model');

		$items = $model::findMany($request->input('ids', []));

		if (! $this->service->requiresVerification($items)) {
			return new JsonResponse(['required' => false]);
		}

		$redirectUrl = $this->service->getRedirectUrl();

		return new JsonResponse([
			'required' => true,
			'redirect_url' => $redirectUrl,
		]);
	}

	public function callback(CallbackRequest $request): JsonResponse
	{
		$result = $this->service->complete(
			$request->string('code')->toString(),
			$request->string('state')->toString(),
		);

		return new JsonResponse([]);
	}
}
