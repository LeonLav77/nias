<?php

declare(strict_types=1);

namespace Vendor\NiasAgeVerification\Dtos;

final class VerificationResultDto
{
	public function __construct(
		public readonly IdTokenClaimsDto $claims,
		public readonly string $idToken,
		public readonly ?string $verificationId = null,
	) {
	}

	/** The audit row's id, which is what the buyer's client is given. */
	public function withVerificationId(string $verificationId): self
	{
		return new self($this->claims, $this->idToken, $verificationId);
	}

	public function isAdult(): bool
	{
		return $this->claims->isAdult;
	}
}
