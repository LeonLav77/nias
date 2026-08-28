<?php

declare(strict_types=1);

namespace LeonLav77\NiasAgeVerification\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use LeonLav77\NiasAgeVerification\Dtos\IdTokenClaimsDto;
use LeonLav77\NiasAgeVerification\Exceptions\TokenReplayedException;

class AgeVerification extends Model
{
	use HasUuids;

	protected $fillable = [
		'id_token',
		'token_hash',
		'is_adult',
		'verified_at',
		'expires_at',
	];

	protected $casts = [
		'is_adult' => 'boolean',
		'verified_at' => 'immutable_datetime',
		'expires_at' => 'immutable_datetime',
	];

	public static function record(IdTokenClaimsDto $claims, string $idToken): self
	{
		$tokenHash = static::hashToken($idToken);

		if (static::where('token_hash', $tokenHash)->exists()) {
			throw new TokenReplayedException('This ID token has already been recorded.');
		}

		$verifiedAt = CarbonImmutable::now();

		try {
			return static::create([
				'id_token' => $idToken,
				'token_hash' => $tokenHash,
				'is_adult' => $claims->isAdult,
				'verified_at' => $verifiedAt,
				'expires_at' => $verifiedAt->addSeconds(static::ttl()),
			]);
		} catch (UniqueConstraintViolationException) {
			throw new TokenReplayedException('This ID token has already been recorded.');
		}
	}

	public static function hashToken(string $idToken): string
	{
		return hash('sha256', $idToken);
	}

	protected static function ttl(): int
	{
		return (int) config('nias-age-verification.verification_ttl');
	}
}
