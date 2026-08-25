<?php

declare(strict_types=1);

namespace LeonLav77\NiasAgeVerification;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Throwable;
use LeonLav77\NiasAgeVerification\Dtos\IdTokenClaimsDto;
use LeonLav77\NiasAgeVerification\Exceptions\InvalidIdTokenException;

class IdTokenValidator
{
	protected const ALGORITHM = 'RS256';

	public function __construct(
		protected SigningKeyStore $keyStore,
	) {
	}

	public function validate(string $idToken, ?string $expectedNonce = null): IdTokenClaimsDto
	{
		$keys = JWK::parseKeySet($this->keyStore->get(), self::ALGORITHM);

		try {
			// Verifies the signature and rejects an expired token.
			$claims = (array) JWT::decode($idToken, $keys);
		} catch (Throwable $e) {
			throw new InvalidIdTokenException('ID token failed verification: ' . $e->getMessage());
		}

		$this->assertClaim($claims, 'iss', config('nias-age-verification.base_url'));
		$this->assertClaim($claims, 'aud', config('nias-age-verification.client_id'));

		if ($expectedNonce !== null) {
			$this->assertClaim($claims, 'nonce', $expectedNonce);
		}

		return IdTokenClaimsDto::fromClaims($claims);
	}

	protected function assertClaim(array $claims, string $claim, string $expected): void
	{
		$actual = $claims[$claim] ?? null;

		if (! is_string($actual) || ! hash_equals($expected, $actual)) {
			throw new InvalidIdTokenException('ID token claim "' . $claim . '" did not match.');
		}
	}
}
