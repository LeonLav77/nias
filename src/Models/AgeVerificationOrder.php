<?php

declare(strict_types=1);

namespace LeonLav77\NiasAgeVerification\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $age_verification_id
 * @property string $order_id
 */
class AgeVerificationOrder extends Model
{
	protected $table = 'age_verification_order';

	public $incrementing = false;

	protected $primaryKey = null;

	public $keyType = 'string';

	protected $fillable = [
		'age_verification_id',
		'order_id',
	];
}
