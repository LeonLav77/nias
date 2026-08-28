<?php

declare(strict_types=1);

namespace LeonLav77\NiasAgeVerification\Exceptions;

use LeonLav77\NiasAgeVerification\Enums\VerificationError;

class VerificationRequiredException extends NiasException
{
	public function __construct(public readonly VerificationError $reason)
	{
		parent::__construct($reason->name);
	}

	public function publicMessage(): string
	{
		return $this->reason->message();
	}
}
