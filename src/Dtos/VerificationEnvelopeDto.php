<?php

declare(strict_types=1);

namespace LeonLav77\NiasAgeVerification\Dtos;

use Illuminate\Http\Request;

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

	public function isForeign(): bool
	{
		if ($this->country === null) {
			return false;
		}

		$domestic = strtoupper(config('nias-age-verification.country'));

		return $this->country !== $domestic;
	}
}
