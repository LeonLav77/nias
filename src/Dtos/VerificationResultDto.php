<?php

declare(strict_types=1);

namespace LeonLav77\NiasAgeVerification\Dtos;

final class VerificationResultDto
{
	public function __construct(
		public readonly IdTokenClaimsDto $claims,
		public readonly string $idToken,
		public readonly string $verificationId,
	) {
	}

	public function isAdult(): bool
	{
		return $this->claims->isAdult;
	}
}
