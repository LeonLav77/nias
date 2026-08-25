<?php

declare(strict_types=1);

namespace LeonLav77\NiasAgeVerification\Contracts;

/**
 * An order that a verification can be attached to.
 *
 * Like IsAgeRestricted this is deliberately Eloquent-free: it asks for an
 * identifier as a string, not for a model's key, so the audit trail can be
 * written against anything the application calls an order.
 *
 * The companion HasAgeVerifications trait satisfies this for an Eloquent model
 * and adds the inverse relation.
 */
interface AgeVerifiableOrder
{
	/** Stable identifier recorded in the audit trail. */
	public function getOrderIdentifier(): string;
}
