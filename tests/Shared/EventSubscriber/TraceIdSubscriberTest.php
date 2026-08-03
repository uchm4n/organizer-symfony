<?php

declare(strict_types=1);

namespace App\Tests\Shared\EventSubscriber;

use App\Shared\EventSubscriber\TraceIdSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelEvents;

class TraceIdSubscriberTest extends TestCase
{
    public function testSubscribedEvents(): void
    {
        $events = TraceIdSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::CONTROLLER, $events);
        $this->assertArrayHasKey(KernelEvents::RESPONSE, $events);
    }
}
