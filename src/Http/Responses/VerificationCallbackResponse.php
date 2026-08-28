<?php

declare(strict_types=1);

namespace LeonLav77\NiasAgeVerification\Http\Responses;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\RedirectResponse;
use LeonLav77\NiasAgeVerification\Enums\VerificationError;

class VerificationCallbackResponse implements Responsable
{
	protected function __construct(
		protected ?VerificationError $error = null,
		protected ?string $verificationId = null,
	) {
	}

	public static function approved(string $verificationId): self
	{
		return new self(verificationId: $verificationId);
	}

	public static function denied(VerificationError $error): self
	{
		return new self(error: $error);
	}

	public function toResponse($request): RedirectResponse
	{
		if ($this->error !== null) {
			$url = config('nias-age-verification.callback_denied');

			$query = [
				'error' => $this->error->value,
				'message' => $this->error->message(),
			];
		} else {
			$url = config('nias-age-verification.callback_approved');

			$query = ['verification_id' => $this->verificationId];
		}

		return new RedirectResponse($url . '?' . http_build_query($query));
	}
}
