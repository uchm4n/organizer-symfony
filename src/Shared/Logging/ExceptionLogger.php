<?php

declare(strict_types=1);

namespace App\Shared\Logging;

use App\Shared\HttpKernel\ProblemRenderer;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ExceptionLogger
{
    private const CLIENT_NOISE = [
        AuthenticationException::class,
        NotFoundHttpException::class,
    ];

    public function __construct(
        private LoggerInterface $logger,
        private RequestStack $requestStack,
    ) {}

    public function log(\Throwable $exception, int $status): void
    {
        $level = $this->determineLevel($exception, $status);
        $request = $this->requestStack->getCurrentRequest();
        $route = $request?->attributes->get('_route') ?? $request?->getPathInfo();

        $context = [
            'status'   => $status,
            'route'    => $route,
            'method'   => $request?->getMethod(),
            'url'      => $request?->getUri(),
            'ip'       => $request?->getClientIp(),
            'trace_id' => $request?->attributes->get('trace_id'),
            'user'     => $request?->getUser(),
        ];

        $message = sprintf(
            '%d %s %s "%s" at %s:%d',
            $status,
            ProblemRenderer::titleForStatus($status),
            $exception::class,
            $this->cleanMessage($exception->getMessage()),
            $this->relativePath($exception->getFile()),
            $exception->getLine(),
        );

        $this->logger->$level($message, $context);
    }

    private function determineLevel(\Throwable $exception, int $status): string
    {
        return match (true) {
            $status >= 500 => 'error',
            in_array($status, [403, 422, 429]) => 'warning',
            $this->isClientNoise($exception) => 'info',
            default => 'info',
        };
    }

    private function isClientNoise(\Throwable $exception): bool
    {
        foreach (self::CLIENT_NOISE as $class) {
            if ($exception instanceof $class) {
                return true;
            }
        }
        return false;
    }

    private function cleanMessage(string $message): string
    {
        $message = trim($message);
        if ($message === '') {
            return 'no message';
        }
        return preg_replace('/\s+/', ' ', $message) ?? $message;
    }

    private function relativePath(string $path): string
    {
        $base = dirname(__DIR__, 2);
        return str_starts_with($path, $base) ? substr($path, strlen($base) + 1) : $path;
    }
}
