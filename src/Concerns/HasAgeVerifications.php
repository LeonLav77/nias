<?php

declare(strict_types=1);

namespace LeonLav77\NiasAgeVerification\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use LeonLav77\NiasAgeVerification\Models\AgeVerification;

trait HasAgeVerifications
{
	public function getOrderIdentifier(): string
	{
		return (string) $this->getKey();
	}

	/** @return BelongsToMany<AgeVerification> */
	public function ageVerifications(): BelongsToMany
	{
		return $this->belongsToMany(
			AgeVerification::class,
			'age_verification_order',
			'order_id',
			'age_verification_id',
		);
	}
}
