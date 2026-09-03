<?php

declare(strict_types=1);

namespace LeonLav77\NiasAgeVerification\Dtos;

use Carbon\CarbonImmutable;

final class VerificationResultDto
{
	public function __construct(
		public readonly IdTokenClaimsDto $claims,
		public readonly string $idToken,
		public readonly string $verificationId,
		public readonly CarbonImmutable $expiresAt,
	) {
	}

	public function isAdult(): bool
	{
		return $this->claims->isAdult;
	}
}
