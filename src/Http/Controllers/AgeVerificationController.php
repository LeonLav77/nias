<?php

declare(strict_types=1);

namespace LeonLav77\NiasAgeVerification\Http\Controllers;

use Illuminate\Http\Request;
use LeonLav77\NiasAgeVerification\AgeVerificationService;
use LeonLav77\NiasAgeVerification\Contracts\IsAgeRestricted;
use LeonLav77\NiasAgeVerification\Dtos\VerificationEnvelopeDto;
use LeonLav77\NiasAgeVerification\Enums\VerificationError;
use LeonLav77\NiasAgeVerification\Exceptions\InvalidIdTokenException;
use LeonLav77\NiasAgeVerification\Exceptions\InvalidStateException;
use LeonLav77\NiasAgeVerification\Exceptions\TokenExchangeException;
use LeonLav77\NiasAgeVerification\Http\Requests\CallbackRequest;
use LeonLav77\NiasAgeVerification\Http\Resources\StartVerificationResource;
use LeonLav77\NiasAgeVerification\Http\Responses\VerificationCallbackResponse;

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

		$envelope = VerificationEnvelopeDto::fromRequest($request);

		$items = $model::findMany($envelope->itemIds);

		if (! $this->service->requiresVerification($items)) {
			return new StartVerificationResource(required: false);
		}

		return new StartVerificationResource(
			required: true,
			redirectUrl: $this->service->getRedirectUrl(),
		);
	}

	public function callback(CallbackRequest $request): VerificationCallbackResponse
	{
		$code = $request->string('code')->toString();

		if ($code === '') {
			return VerificationCallbackResponse::denied(VerificationError::CANCELLED);
		}

		try {
			$result = $this->service->complete($code, $request->string('state')->toString());
		} catch (InvalidStateException) {
			return VerificationCallbackResponse::denied(VerificationError::STATE_EXPIRED);
		} catch (TokenExchangeException) {
			return VerificationCallbackResponse::denied(VerificationError::EXCHANGE_FAILED);
		} catch (InvalidIdTokenException) {
			return VerificationCallbackResponse::denied(VerificationError::INVALID_TOKEN);
		}

		if (! $result->isAdult()) {
			return VerificationCallbackResponse::denied(VerificationError::NOT_ADULT);
		}

		return VerificationCallbackResponse::approved($result->verificationId);
	}
}
