<?php

declare(strict_types=1);

namespace Vendor\NiasAgeVerification\Enums;

enum ResponseType: string
{
	case CODE = 'code';

	public static function getDefault(): self
	{
		return self::CODE;
	}
}
