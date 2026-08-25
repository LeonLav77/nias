<?php

declare(strict_types=1);

namespace LeonLav77\NiasAgeVerification;

use Illuminate\Support\Facades\Cache;

class SigningKeyStore
{
	private const CACHE_KEY = 'nias-age-verification:signing-keys';

	public function __construct(
		protected NiasApiHandler $api,
	) {
	}

	public function get(): array
	{
		return Cache::remember(
			self::CACHE_KEY,
			config('nias-age-verification.signing_keys_ttl'),
			fn (): array => $this->api->fetchSigningKeys(),
		);
	}
}
