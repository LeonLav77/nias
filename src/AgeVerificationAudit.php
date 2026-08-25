<?php

declare(strict_types=1);

namespace Vendor\NiasAgeVerification;

use Vendor\NiasAgeVerification\Dtos\VerificationResultDto;
use Vendor\NiasAgeVerification\Exceptions\InvalidIdTokenException;
use Vendor\NiasAgeVerification\Models\AgeVerification;
use Vendor\NiasAgeVerification\Models\AgeVerificationOrder;

class AgeVerificationAudit
{
	public function record(VerificationResultDto $result): AgeVerification
	{
		return AgeVerification::record($result);
	}

	public function attachOrder(string $verificationId, string $orderId): AgeVerification
	{
		$verification = AgeVerification::find($verificationId);

		if ($verification === null) {
			throw new InvalidIdTokenException('No recorded verification with that id.');
		}

		AgeVerificationOrder::firstOrCreate([
			'age_verification_id' => $verification->getKey(),
			'order_id' => $orderId,
		]);

		return $verification;
	}
}
