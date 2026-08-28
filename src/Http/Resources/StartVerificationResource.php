<?php

declare(strict_types=1);

namespace LeonLav77\NiasAgeVerification\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StartVerificationResource extends JsonResource
{
	public function __construct(
		public string $redirectUrl,
	) {
		parent::__construct(null);
	}

	/** @return array<string, mixed> */
	public function toArray(Request $request): array
	{
		return [
			'redirect_url' => $this->redirectUrl,
		];
	}
}
