<?php

declare(strict_types=1);

namespace LeonLav77\NiasAgeVerification\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use LeonLav77\NiasAgeVerification\Dtos\VerificationResultDto;

/**
 * @property string $id
 * @property string $id_token
 * @property string $token_hash
 * @property bool $is_adult
 * @property \Carbon\CarbonImmutable $verified_at
 * @property \Carbon\CarbonImmutable $expires_at
 */
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

	public static function record(VerificationResultDto $result): self
	{
		$verifiedAt = CarbonImmutable::now();

		return static::create([
			'id_token' => $result->idToken,
			'token_hash' => static::hashToken($result->idToken),
			'is_adult' => $result->isAdult(),
			'verified_at' => $verifiedAt,

			// Our own window, not the token's. The token's `exp` has already done
			// its job in IdTokenValidator and is of no further use here.
			'expires_at' => $verifiedAt->addSeconds(static::ttl()),
		]);
	}

	public static function findByToken(string $idToken): ?self
	{
		return static::where('token_hash', static::hashToken($idToken))->first();
	}

	public static function hashToken(string $idToken): string
	{
		return hash('sha256', $idToken);
	}

	protected static function ttl(): int
	{
		return (int) config('nias-age-verification.verification_ttl');
	}

	/** An adult verdict that has not lapsed. Anything else denies the sale. */
	public function permitsPurchase(): bool
	{
		return $this->is_adult && $this->expires_at->isFuture();
	}
}
