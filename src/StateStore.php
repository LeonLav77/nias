<?php

declare(strict_types=1);

namespace LeonLav77\NiasAgeVerification;

class StateStore
{
	protected const PREFIX = 'nias-age-verification:state:';

	public function put(string $state, string $codeVerifier, string $nonce): void
	{
		$name = self::PREFIX . $state;
		$data = [
			'code_verifier' => $codeVerifier,
			'nonce' => $nonce,
		];
		$ttl = (int) config('nias-age-verification.state_ttl');

		cache()->put($name, $data, $ttl);
	}

	public function pull(string $state): ?array
	{
		return cache()->pull(self::PREFIX . $state);
	}
}
