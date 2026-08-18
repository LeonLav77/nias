<?php

declare(strict_types=1);

namespace Vendor\NiasAgeVerification\Contracts;

interface IsAgeRestricted
{
    public function isAgeRestricted(): bool;
}
