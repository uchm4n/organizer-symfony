<?php

declare(strict_types=1);

namespace App\Shared\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

final class UnsupportedApiVersionException extends HttpException
{
    public function __construct(string $requested, array $supported)
    {
        parent::__construct(
            400,
            sprintf(
                'Unsupported API version "%s". Supported versions: %s.',
                $requested,
                implode(', ', $supported),
            ),
            null,
            ['Content-Type' => 'application/problem+json'],
        );
    }
}
