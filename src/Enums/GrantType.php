<?php

declare(strict_types=1);

namespace Vendor\NiasAgeVerification\Enums;

enum GrantType: string
{
	case AUTHORIZATION_CODE = 'authorization_code';

	public static function getDefault(): self
	{
		return self::AUTHORIZATION_CODE;
	}
}
