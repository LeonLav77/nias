<?php

declare(strict_types=1);

namespace Vendor\NiasAgeVerification\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Vendor\NiasAgeVerification\AgeVerificationService;
use Vendor\NiasAgeVerification\Contracts\IsAgeRestricted;
use Vendor\NiasAgeVerification\Models\AgeVerification;

class RequireAgeVerification
{
	public function __construct(
		protected AgeVerificationService $service,
	) {
	}

	public function handle(Request $request, Closure $next): Response
	{
		/** @var class-string<IsAgeRestricted> $model */
		$model = config('nias-age-verification.product_model');

		$items = $model::findMany($this->itemIds($request));

		if (! $this->service->requiresVerification($items)) {
			return $next($request);
		}

		$verification = AgeVerification::find($request->string('verification_id')->toString());

		if ($verification === null || ! $verification->permitsPurchase()) {
			abort(Response::HTTP_FORBIDDEN, __('Age verification is required for one or more items in this order.'));
		}

		return $next($request);
	}

	protected function itemIds(Request $request): array
	{
		return collect($request->input('items', []))
			->pluck('id')
			->filter()
			->values()
			->all();
	}
}
