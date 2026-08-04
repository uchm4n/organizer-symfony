<?php

declare(strict_types=1);

namespace App\Tests\Shared\EventSubscriber;

use App\Shared\Exception\UnsupportedApiVersionException;
use App\Shared\EventSubscriber\ApiVersionSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiVersionSubscriberTest extends TestCase
{
    public function testSubscribedEvents(): void
    {
        $events = ApiVersionSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::CONTROLLER, $events);
    }

    public function testHeaderVersionIsStoredOnRequest(): void
    {
        $request = Request::create('/api/v1/user');
        $request->headers->set('X-API-Version', 'v1');

        (new ApiVersionSubscriber())->onController($this->createEvent($request));

        $this->assertSame('v1', $request->attributes->get('api_version'));
    }

    public function testQueryVersionStillWorks(): void
    {
        $request = Request::create('/api/v1/user?api_version=v1');

        (new ApiVersionSubscriber())->onController($this->createEvent($request));

        $this->assertSame('v1', $request->attributes->get('api_version'));
    }

    public function testUnsupportedVersionThrows(): void
    {
        $request = Request::create('/api/v1/user');
        $request->headers->set('X-API-Version', 'v2');

        $this->expectException(UnsupportedApiVersionException::class);

        (new ApiVersionSubscriber())->onController($this->createEvent($request));
    }

    private function createEvent(Request $request): ControllerEvent
    {
        return new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            static fn () => null,
            $request,
            HttpKernelInterface::MAIN_REQUEST,
        );
    }
}
