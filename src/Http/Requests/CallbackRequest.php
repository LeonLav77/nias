<?php

declare(strict_types=1);

namespace Vendor\NiasAgeVerification\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CallbackRequest extends FormRequest
{
	public function authorize(): bool
	{
		return true;
	}

	public function rules(): array
	{
		return [
			'state' => ['required', 'string'],
		];
	}
}
