<?php

declare(strict_types=1);

namespace LeonLav77\NiasAgeVerification\Exceptions;

use RuntimeException;

/**
 * Base for every failure in the Nias flow, so callers can deny on any of them
 * with a single catch.
 */
abstract class NiasException extends RuntimeException
{
}
