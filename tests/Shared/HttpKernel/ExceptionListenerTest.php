<?php

declare(strict_types=1);

namespace App\Tests\Shared\HttpKernel;

use App\Shared\HttpKernel\ExceptionListener;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionListenerTest extends TestCase
{
    public function testSubscribedEvents(): void
    {
        $events = ExceptionListener::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::EXCEPTION, $events);
    }
}
