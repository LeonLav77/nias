<?php

declare(strict_types=1);

namespace LeonLav77\NiasAgeVerification\Exceptions;

use LeonLav77\NiasAgeVerification\Enums\VerificationError;

class ProductMismatchException extends NiasException
{
	public function publicMessage(): string
	{
		return VerificationError::PRODUCT_MISMATCH->message();
	}
}
