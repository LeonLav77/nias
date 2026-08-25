<?php

declare(strict_types=1);

namespace LeonLav77\NiasAgeVerification\Contracts;

interface AgeVerifiableOrder
{
	public function getOrderIdentifier(): string;
}
