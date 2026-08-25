<?php

declare(strict_types=1);

namespace Vendor\NiasAgeVerification\Dtos;

use Carbon\CarbonImmutable;
use Vendor\NiasAgeVerification\Exceptions\InvalidIdTokenException;

/**
 * The claims we act on, taken from a token that has already been verified.
 *
 * `sub` is deliberately absent: it is a pairwise pseudonym, is not needed for
 * the age decision, and leaving it off the DTO makes misuse impossible.
 */
final class IdTokenClaimsDto
{
	public function __construct(
		public readonly bool $isAdult,
		public readonly CarbonImmutable $issuedAt,
		public readonly CarbonImmutable $expiresAt,
	) {
	}

	/** @param array<string, mixed> $claims */
	public static function fromClaims(array $claims): self
	{
		$isAdult = $claims['age.alcohol'] ?? null;

		// A missing or non-boolean claim is a denial, never a pass.
		if (! is_bool($isAdult)) {
			throw new InvalidIdTokenException('ID token had no usable age.alcohol claim.');
		}

		return new self(
			isAdult: $isAdult,
			issuedAt: self::timestamp($claims, 'iat'),
			expiresAt: self::timestamp($claims, 'exp'),
		);
	}

	/** @param array<string, mixed> $claims */
	private static function timestamp(array $claims, string $claim): CarbonImmutable
	{
		$value = $claims[$claim] ?? null;

		if (! is_numeric($value)) {
			throw new InvalidIdTokenException('ID token had no usable ' . $claim . ' claim.');
		}

		return CarbonImmutable::createFromTimestampUTC((int) $value);
	}
}
