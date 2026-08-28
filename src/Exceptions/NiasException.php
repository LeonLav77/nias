<?php

declare(strict_types=1);

namespace LeonLav77\NiasAgeVerification\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

abstract class NiasException extends RuntimeException
{
	protected int $status = Response::HTTP_FORBIDDEN;

	public function publicMessage(): ?string
	{
		return null;
	}

	public function render(Request $request): ?JsonResponse
	{
		$message = $this->publicMessage();

		if ($message === null || ! $request->expectsJson()) {
			return null;
		}

		return new JsonResponse(['message' => $message], $this->status);
	}
}
