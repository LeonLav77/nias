<?php

declare(strict_types=1);

namespace LeonLav77\NiasAgeVerification;

use LeonLav77\NiasAgeVerification\Contracts\AgeVerifiableOrder;
use LeonLav77\NiasAgeVerification\Dtos\VerificationResultDto;
use LeonLav77\NiasAgeVerification\Exceptions\InvalidIdTokenException;
use LeonLav77\NiasAgeVerification\Models\AgeVerification;
use LeonLav77\NiasAgeVerification\Models\AgeVerificationOrder;

class AgeVerificationAudit
{
	public function record(VerificationResultDto $result): AgeVerification
	{
		return AgeVerification::record($result);
	}

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
}
