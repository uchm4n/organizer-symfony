<?php

declare(strict_types=1);

namespace App\Shared\EventSubscriber;

use App\Shared\Exception\UnsupportedApiVersionException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class ApiVersionSubscriber implements EventSubscriberInterface
{
    private const SUPPORTED_VERSIONS = ['v1'];

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['onController', 60],
        ];
    }

    public function onController(ControllerEvent $event): void
    {
        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $version = $request->headers->get('X-API-Version')
            ?? $request->query->get('api_version', 'v1');

        if (!in_array($version, self::SUPPORTED_VERSIONS, true)) {
            throw new UnsupportedApiVersionException($version, self::SUPPORTED_VERSIONS);
        }

        $request->attributes->set('api_version', $version);
    }
}
