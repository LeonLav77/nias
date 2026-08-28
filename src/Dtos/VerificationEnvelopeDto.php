<?php

declare(strict_types=1);

namespace LeonLav77\NiasAgeVerification\Dtos;

use Illuminate\Http\Request;
use LeonLav77\NiasAgeVerification\Enums\VerificationError;
use LeonLav77\NiasAgeVerification\Models\AgeVerification;

final class VerificationEnvelopeDto
{
	/** @param list<string|int> $itemIds */
	public function __construct(
		public readonly ?string $id,
		public readonly ?string $country,
		public readonly array $itemIds,
	) {
	}

	public static function fromRequest(Request $request): self
	{
		$country = $request->input('verification.country');

		if ($country !== null) {
			$country = strtoupper($country);
		}

		return new self(
			id: $request->input('verification.id'),
			country: $country,
			itemIds: $request->input('verification.items', []),
		);
	}

	public function denialReason(): ?VerificationError
	{
		// Verification does not exist in the request
		if ($this->id === null) {
			return VerificationError::REQUIRED;
		}

		$verification = AgeVerification::find($this->id);

		if ($verification === null) {
			return VerificationError::REQUIRED;
		}

		if (! $verification->is_adult) {
			return VerificationError::NOT_ADULT;
		}

		if (! $verification->expires_at->isFuture()) {
			return VerificationError::EXPIRED;
		}

		return null;
	}

	public function isForeign(): bool
	{
		if ($this->country === null) {
			return false;
		}

		$domestic = strtoupper(config('nias-age-verification.country'));

		return $this->country !== $domestic;
	}
}
