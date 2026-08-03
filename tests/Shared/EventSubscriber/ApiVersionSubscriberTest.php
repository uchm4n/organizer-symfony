<?php

declare(strict_types=1);

namespace App\Tests\Shared\EventSubscriber;

use App\Shared\EventSubscriber\ApiVersionSubscriber;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiVersionSubscriberTest extends TestCase
{
    public function testSubscribedEvents(): void
    {
        $events = ApiVersionSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::CONTROLLER, $events);
    }
}
