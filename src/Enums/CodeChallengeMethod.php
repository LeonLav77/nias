<?php

declare(strict_types=1);

namespace LeonLav77\NiasAgeVerification\Enums;

enum CodeChallengeMethod: string
{
	case S256 = 'S256';

	public static function getDefault(): self
	{
		return self::S256;
	}
}
