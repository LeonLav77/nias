<?php

declare(strict_types=1);

namespace LeonLav77\NiasAgeVerification;

use LeonLav77\NiasAgeVerification\Contracts\AgeVerifiableOrder;
use LeonLav77\NiasAgeVerification\Exceptions\InvalidIdTokenException;
use LeonLav77\NiasAgeVerification\Models\AgeVerification;
use LeonLav77\NiasAgeVerification\Models\AgeVerificationOrder;

class AgeVerificationManager
{
	public function attachOrder(AgeVerifiableOrder $order, ?string $verificationId = null): ?AgeVerification
	{
		if (! config('nias-age-verification.enabled')) {
			return null;
		}

		if ($verificationId === null || $verificationId === '') {
			return null;
		}

		$verification = AgeVerification::find($verificationId);

		if ($verification === null) {
			throw new InvalidIdTokenException('No recorded verification with that id.');
		}

		AgeVerificationOrder::firstOrCreate([
			'age_verification_id' => $verification->getKey(),
			'order_id' => $order->getOrderIdentifier(),
		]);

		return $verification;
	}

	public function securityCheck(
		array $requestOrderIds,
		array $verificationOrderIds,
		string $requestCountry,
		string $verificationCountry
	): void
	{
		if (strtoupper($requestCountry) !== strtoupper($verificationCountry)) {
			throw new InvalidIdTokenException('Verification country does not match the request.');
		}

		if (! config('nias-age-verification.enabled')) {
			return;
		}

		$request = array_unique(array_map('strval', $requestOrderIds));
		$verified = array_unique(array_map('strval', $verificationOrderIds));

		sort($request);
		sort($verified);

		if ($request !== $verified) {
			throw new InvalidIdTokenException('Verification was not issued for these products.');
		}
	}
}
