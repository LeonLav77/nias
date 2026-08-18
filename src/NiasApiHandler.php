<?php

declare(strict_types=1);

namespace Vendor\NiasAgeVerification;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Vendor\NiasAgeVerification\Enums\GrantType;
use Vendor\NiasAgeVerification\Exceptions\TokenExchangeException;

class NiasApiHandler
{
	protected PendingRequest $client;

	public function __construct()
	{
		$baseUrl = config('nias-age-verification.base_url');
		$timeout = config('nias-age-verification.http_timeout');

		$this->client = Http::baseUrl($baseUrl)
			->timeout($timeout)
			->asForm()
			->acceptJson();
	}

	public function exchangeCode(string $code, string $codeVerifier): string
	{
		$grantType = GrantType::AUTHORIZATION_CODE->value;
		$redirectUri = config('nias-age-verification.redirect_uri');
		$clientId = config('nias-age-verification.client_id');

		$response = $this->client->post('/token', [
			'grant_type' => $grantType,
			'code' => $code,
			'redirect_uri' => $redirectUri,
			'client_id' => $clientId,
			'code_verifier' => $codeVerifier,
		]);

		if ($response->failed()) {
			throw new TokenExchangeException('Token endpoint returned ' . $response->status() . '.');
		}

		$idToken = $response->json('id_token');

		if (! is_string($idToken) || $idToken === '') {
			throw new TokenExchangeException('Token response contained no id_token.');
		}

		return $idToken;
	}

	public function fetchSigningKeys(): array
	{
		$response = $this->client->asJson()->get('/jwks');

		if ($response->failed()) {
			throw new TokenExchangeException('JWKS endpoint returned ' . $response->status() . '.');
		}

		return $response->json();
	}
}
