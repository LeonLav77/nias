<?php

declare(strict_types=1);

namespace LeonLav77\NiasAgeVerification\Enums;

enum VerificationError: string
{
	/** The buyer came back from Nias without an authorisation code. */
	case CANCELLED = 'cancelled';

	/** The state was unknown, replayed, or older than `state_ttl`. */
	case STATE_EXPIRED = 'state_expired';

	/** Nias refused the code-for-token exchange. */
	case EXCHANGE_FAILED = 'exchange_failed';

	/** The token came back malformed, unsigned, or with unusable claims. */
	case INVALID_TOKEN = 'invalid_token';

	/** Nias verified the buyer and said they are not old enough. */
	case NOT_ADULT = 'not_adult';

	/** No usable verification for a cart that needs one. */
	case REQUIRED = 'required';

	/** The verification is real but older than `verification_ttl`. */
	case EXPIRED = 'expired';

	public function message(): string
	{
		return (string) __('nias-age-verification::messages.' . $this->value);
	}
}
