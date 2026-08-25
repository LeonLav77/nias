<?php

declare(strict_types=1);

namespace Vendor\NiasAgeVerification\Exceptions;



/**
 * The ID token failed signature or claim validation.
 *
 * Messages must never carry the token itself.
 */
class InvalidIdTokenException extends NiasException
{
}
