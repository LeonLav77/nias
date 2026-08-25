<?php

declare(strict_types=1);

namespace LeonLav77\NiasAgeVerification\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use LeonLav77\NiasAgeVerification\AgeVerificationService;
use LeonLav77\NiasAgeVerification\Contracts\IsAgeRestricted;
use LeonLav77\NiasAgeVerification\Exceptions\NiasException;
use LeonLav77\NiasAgeVerification\Http\Requests\CallbackRequest;
use LeonLav77\NiasAgeVerification\Http\Resources\StartVerificationResource;

class AgeVerificationController
{
	public function __construct(
		protected AgeVerificationService $service,
	) {
	}

	// TODO: ADD VALIDATION
	public function start(Request $request): StartVerificationResource
	{
		/** @var class-string<IsAgeRestricted> $model */
		$model = config('nias-age-verification.product_model');

		$items = $model::findMany($request->input('product_ids', []));

		if (! $this->service->requiresVerification($items)) {
			return new StartVerificationResource(required: false);
		}

		return new StartVerificationResource(
			required: true,
			redirectUrl: $this->service->getRedirectUrl(),
		);
	}

	public function callback(CallbackRequest $request): RedirectResponse
	{
		$code = $request->string('code')->toString();
		$deniedUrl = config('nias-age-verification.callback_denied');
		$acceptedUrl = config('nias-age-verification.callback_approved');

		if ($code === '') {
			return new RedirectResponse($deniedUrl);
		}

		try {
			$result = $this->service->complete($code, $request->string('state')->toString());
		} catch (NiasException) {
			return new RedirectResponse($deniedUrl);
		}
		
		if (! $result->isAdult()) {
			return new RedirectResponse($deniedUrl);
		}

		return new RedirectResponse(
			$acceptedUrl . '?' . http_build_query(['verification_id' => $result->verificationId]),
		);
	}
}
