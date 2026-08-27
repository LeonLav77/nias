<?php

declare(strict_types=1);

namespace LeonLav77\NiasAgeVerification\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use LeonLav77\NiasAgeVerification\AgeVerificationService;
use LeonLav77\NiasAgeVerification\Contracts\IsAgeRestricted;
use LeonLav77\NiasAgeVerification\Dtos\VerificationEnvelopeDto;
use LeonLav77\NiasAgeVerification\Models\AgeVerification;

class RequireAgeVerification
{
	public function __construct(
		protected AgeVerificationService $service,
	) {
	}

	public function handle(Request $request, Closure $next): Response
	{
		if (! config('nias-age-verification.enabled')) {
			return $next($request);
		}

		$envelope = VerificationEnvelopeDto::fromRequest($request);

		if ($envelope->isForeign()) {
			return $next($request);
		}

		/** @var class-string<IsAgeRestricted> $model */
		$model = config('nias-age-verification.product_model');

		$items = $model::findMany($envelope->itemIds);

		if (! $this->service->requiresVerification($items)) {
			return $next($request);
		}

		$verification = $envelope->id === null ? null : AgeVerification::find($envelope->id);

		if ($verification === null || ! $verification->permitsPurchase()) {
			abort(Response::HTTP_FORBIDDEN, __('Age verification is required for one or more items in this order.'));
		}

		return $next($request);
	}
}
