<?php

declare(strict_types=1);

namespace LeonLav77\NiasAgeVerification\Enums;

enum Scope: string
{
	case AGE_ALCOHOL = 'age.alcohol';

	public static function getDefault(): self
	{
		return self::AGE_ALCOHOL;
	}
}
