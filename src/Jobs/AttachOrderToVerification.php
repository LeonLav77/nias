<?php

declare(strict_types=1);

namespace LeonLav77\NiasAgeVerification\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use LeonLav77\NiasAgeVerification\Contracts\AgeVerifiableOrder;
use LeonLav77\NiasAgeVerification\Models\AgeVerification;
use LeonLav77\NiasAgeVerification\Models\AgeVerificationOrder;

class AttachOrderToVerification implements ShouldQueue
{
	use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;


	public function __construct(
		protected readonly AgeVerifiableOrder $order,
		protected readonly ?AgeVerification $verification = null,
	) {
	}

	public function handle(): void
	{
		if ($this->verification === null) {
			return;
		}

		AgeVerificationOrder::firstOrCreate([
			'age_verification_id' => $this->verification->getKey(),
			'order_id' => $this->order->getOrderIdentifier(),
		]);
	}
}
