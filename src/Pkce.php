<?php

declare(strict_types=1);

namespace LeonLav77\NiasAgeVerification;

class Pkce
{
	public static function verifier(): string
	{
		return self::base64url(random_bytes(32));
	}

	public static function challenge(string $verifier): string
	{
		return self::base64url(hash('sha256', $verifier, true));
	}

	public static function state(): string
	{
		return bin2hex(random_bytes(16));
	}

	public static function nonce(): string
	{
		return bin2hex(random_bytes(16));
	}

	protected static function base64url(string $bytes): string
	{
		return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
	}
}
