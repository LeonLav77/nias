<?php

declare(strict_types=1);

namespace LeonLav77\NiasAgeVerification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class InitializeVerificationRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			'verification' => ['array'],
			'verification.items' => ['array'],
			'verification.items.*' => ['required', 'uuid', $this->productExists()],
		];
	}

	protected function productExists(): Exists
	{
		$model = config('nias-age-verification.product_model');
		$product = new $model;

		return Rule::exists($product->getTable(), $product->getKeyName());
	}
}
