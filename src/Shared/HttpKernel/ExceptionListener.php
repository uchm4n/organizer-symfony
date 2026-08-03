<?php

declare(strict_types=1);

namespace App\Shared\HttpKernel;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Validator\Exception\ValidationException;

final class ExceptionListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onException', -128],
        ];
    }

    public function onException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof HandlerFailedException) {
            $wrapped = $exception->getWrappedExceptions();
            $exception = $wrapped[0] ?? $exception;
        }

        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $response = match (true) {
            $exception instanceof ValidationException => ProblemRenderer::response(
                422,
                'Unprocessable Entity',
                'Validation failed.',
                ['errors' => $exception->getConstraint()->payload ?? []]
            ),
            $exception instanceof AuthenticationException => ProblemRenderer::response(
                401,
                'Unauthorized',
                'Invalid or expired token.'
            ),
            $exception instanceof AccessDeniedException => ProblemRenderer::response(
                403,
                'Forbidden',
                'Insufficient permissions.'
            ),
            $exception instanceof NotFoundHttpException => ProblemRenderer::response(
                404,
                'Not Found',
                'Resource not found.'
            ),
            $exception instanceof HttpException => ProblemRenderer::response(
                $exception->getStatusCode(),
                ProblemRenderer::titleForStatus($exception->getStatusCode()),
                $exception->getMessage()
            ),
            default => ProblemRenderer::response(
                500,
                'Internal Server Error',
                in_array($request->server->get('APP_ENV'), ['dev', 'test'])
                    ? $exception->getMessage()
                    : 'An unexpected error occurred. Please try again later.'
            ),
        };

        $event->setResponse($response);
    }
}
