<?php

declare(strict_types=1);

namespace App\Tests\Shared\HttpKernel;

use App\Item\Message\GetItem;
use App\Shared\Exception\ResourceNotFoundException;
use App\Shared\HttpKernel\ExceptionListener;
use App\Shared\Logging\ExceptionLogger;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\HandlerFailedException;

class ExceptionListenerTest extends TestCase
{
    public function testSubscribedEvents(): void
    {
        $events = ExceptionListener::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::EXCEPTION, $events);
    }

    public function testHandlerFailedExceptionUsesWrappedResourceNotFoundStatus(): void
    {
        $listener = new ExceptionListener(
            new ExceptionLogger(new NullLogger(), new RequestStack()),
        );
        $request = Request::create('/api/v1/items/123');
        $request->server->set('APP_ENV', 'test');

        $exception = new HandlerFailedException(
            new Envelope(new GetItem(123)),
            [
                'App\\Item\\MessageHandler\\GetItemHandler::__invoke' => ResourceNotFoundException::forResource('Item'),
            ],
        );

        $event = new ExceptionEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MAIN_REQUEST,
            $exception,
        );

        $listener->onException($event);

        $response = $event->getResponse();

        $this->assertNotNull($response);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringContainsString('Item not found.', (string) $response->getContent());
    }
}
