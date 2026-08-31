<?php

declare(strict_types=1);

namespace LeonLav77\NiasAgeVerification;

use LeonLav77\NiasAgeVerification\Contracts\IsAgeRestricted;
use LeonLav77\NiasAgeVerification\Enums\VerificationError;
use LeonLav77\NiasAgeVerification\Exceptions\VerificationRequiredException;
use LeonLav77\NiasAgeVerification\Models\AgeVerification;

class AgeVerificationManager
{
	public function assertVerified(iterable $items, ?string $verificationId, ?string $country = null): ?AgeVerification
	{
		if (! config('nias-age-verification.enabled')) {
			return null;
		}

		if ($this->isForeign($country)) {
			return null;
		}

		if (! $this->requiresVerification($items)) {
			return null;
		}

		if ($verificationId === null || $verificationId === '') {
			throw new VerificationRequiredException(VerificationError::REQUIRED);
		}

		$verification = AgeVerification::find($verificationId);

		if ($verification === null) {
			throw new VerificationRequiredException(VerificationError::REQUIRED);
		}

		if (! $verification->is_adult) {
			throw new VerificationRequiredException(VerificationError::NOT_ADULT);
		}

		if (! $verification->expires_at->isFuture()) {
			throw new VerificationRequiredException(VerificationError::EXPIRED);
		}

		return $verification;
	}

	/** @param iterable<IsAgeRestricted> $items */
	protected function requiresVerification(iterable $items): bool
	{
		foreach ($items as $item) {
			if ($item->isAgeRestricted()) {
				return true;
			}
		}

		return false;
	}

	protected function isForeign(?string $country): bool
	{
		if ($country === null || $country === '') {
			return false;
		}

		$domestic = strtoupper(trim((string) config('nias-age-verification.country')));

		return strtoupper(trim($country)) !== $domestic;
	}
}
