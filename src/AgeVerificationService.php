<?php

declare(strict_types=1);

namespace LeonLav77\NiasAgeVerification;

use LeonLav77\NiasAgeVerification\Dtos\VerificationResultDto;
use LeonLav77\NiasAgeVerification\Enums\CodeChallengeMethod;
use LeonLav77\NiasAgeVerification\Enums\ResponseType;
use LeonLav77\NiasAgeVerification\Enums\Scope;
use LeonLav77\NiasAgeVerification\Exceptions\InvalidStateException;
use LeonLav77\NiasAgeVerification\Models\AgeVerification;

class AgeVerificationService
{
    public function __construct(
        protected StateStore $stateStore,
        protected NiasApiHandler $api,
        protected IdTokenValidator $validator,
    ) {
    }

    public function getRedirectUrl(): string
    {
        $state = Pkce::state();
        $nonce = Pkce::nonce();
        $codeVerifier = Pkce::verifier();
        $codeChallenge = Pkce::challenge($codeVerifier);

        $baseUrl = config('nias-age-verification.base_url');
        $clientId = config('nias-age-verification.client_id');
        $redirectUri = config('nias-age-verification.redirect_uri');

        $responseType = ResponseType::CODE;
        $scope = Scope::AGE_ALCOHOL;
        $codeChallengeMethod = CodeChallengeMethod::S256;

        $query = http_build_query([
            'response_type' => $responseType->value,
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => $scope->value,
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => $codeChallengeMethod->value,
        ]);

        $this->stateStore->put($state, $codeVerifier, $nonce);

        return $baseUrl . '/authorize?' . $query;
    }

    public function complete(string $code, string $state): VerificationResultDto
    {
        $stored = $this->stateStore->pull($state);

        if ($stored === null) {
            throw new InvalidStateException();
        }

        $idToken = $this->api->exchangeCode($code, $stored['code_verifier']);

        $claims = $this->validator->validate($idToken, $stored['nonce']);

        $verification = AgeVerification::record($claims, $idToken);

        return new VerificationResultDto($claims, $idToken, (string) $verification->getKey());
    }
}
