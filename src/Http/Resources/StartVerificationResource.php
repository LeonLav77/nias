<?php

declare(strict_types=1);

namespace Vendor\NiasAgeVerification\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property-read bool $required
 * @property-read string|null $redirectUrl
 */
class StartVerificationResource extends JsonResource
{
	public function __construct(
		public bool $required,
		public ?string $redirectUrl = null,
	) {
		parent::__construct(null);
	}

	/** @return array<string, mixed> */
	public function toArray(Request $request): array
	{
		return [
			'required' => $this->required,
			'redirect_url' => $this->redirectUrl,
		];
	}
}
