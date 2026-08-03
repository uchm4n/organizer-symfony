<?php

declare(strict_types=1);

namespace App\Shared\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class TraceIdSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => ['onController', 70],
            KernelEvents::RESPONSE   => ['onResponse', -100],
        ];
    }

    public function onController(ControllerEvent $event): void
    {
        $request = $event->getRequest();

        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $traceId = $request->headers->get('X-Trace-Id') ?: bin2hex(random_bytes(4));

        $request->attributes->set('trace_id', $traceId);
    }

    public function onResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $traceId = $request->attributes->get('trace_id');

        if ($traceId) {
            $event->getResponse()->headers->set('X-Trace-Id', $traceId);
        }
    }
}
