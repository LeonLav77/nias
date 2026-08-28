<?php

declare(strict_types=1);

namespace LeonLav77\NiasAgeVerification\Http\Controllers;

use LeonLav77\NiasAgeVerification\AgeVerificationService;
use LeonLav77\NiasAgeVerification\Enums\VerificationError;
use LeonLav77\NiasAgeVerification\Exceptions\InvalidIdTokenException;
use LeonLav77\NiasAgeVerification\Exceptions\InvalidStateException;
use LeonLav77\NiasAgeVerification\Exceptions\TokenExchangeException;
use LeonLav77\NiasAgeVerification\Exceptions\TokenReplayedException;
use LeonLav77\NiasAgeVerification\Http\Requests\CallbackRequest;
use LeonLav77\NiasAgeVerification\Http\Resources\StartVerificationResource;
use LeonLav77\NiasAgeVerification\Http\Responses\VerificationCallbackResponse;

class AgeVerificationController
{
	public function __construct(
		protected AgeVerificationService $service,
	) {
	}

	/**
	 * Start a verification.
	 *
	 * Whether the cart needs one is the frontend's call; the binding decision is
	 * made again at checkout against the order's own items.
	 */
	public function initialize(): StartVerificationResource
	{
		return new StartVerificationResource(
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
		} catch (TokenReplayedException) {
			return VerificationCallbackResponse::denied(VerificationError::TOKEN_REPLAYED);
		}

		if (! $result->isAdult()) {
			return VerificationCallbackResponse::denied(VerificationError::NOT_ADULT);
		}

		return VerificationCallbackResponse::approved($result->verificationId);
	}
}
