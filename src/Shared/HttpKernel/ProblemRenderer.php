<?php

declare(strict_types=1);

namespace App\Shared\HttpKernel;

use App\Shared\DTO\ProblemResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

final class ProblemRenderer
{
    public static function response(
        int $status,
        string $title,
        string $detail,
        array $extra = [],
    ): JsonResponse {
        $problem = new ProblemResponse(
            status: $status,
            title: $title,
            detail: $detail,
            extra: $extra,
        );

        return new JsonResponse(
            data: $problem->toArray(),
            status: $status,
            headers: ['Content-Type' => 'application/problem+json'],
        );
    }

    public static function titleForStatus(int $status): string
    {
        return match ($status) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            409 => 'Conflict',
            422 => 'Unprocessable Entity',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            default => 'Error',
        };
    }
}
