<?php

declare(strict_types=1);

namespace App\Tests\Integration\User\MessageHandler;

use App\User\Entity\User;
use App\User\Message\GetUser;
use App\User\MessageHandler\GetUserHandler;
use App\Tests\Integration\IntegrationTestCase;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

class GetUserHandlerIntegrationTest extends IntegrationTestCase
{
    private GetUserHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = self::getContainer()->get(GetUserHandler::class);
    }

    public function testGetExistingUser(): void
    {
        $user = $this->createUser(email: 'existing@example.com');

        $result = ($this->handler)(new GetUser($user->getId()));

        $this->assertInstanceOf(User::class, $result);
        $this->assertSame('existing@example.com', $result->getEmail());
    }

    public function testGetNonExistingUser(): void
    {
        $this->expectException(UnrecoverableMessageHandlingException::class);

        ($this->handler)(new GetUser(99999));
    }

    public function testGetUserReturnsCorrectData(): void
    {
        $user = $this->createUser(email: 'data@example.com', password: 'pass', role: \App\User\Enum\Role::Admin);

        $result = ($this->handler)(new GetUser($user->getId()));

        $this->assertSame('data@example.com', $result->getEmail());
        $this->assertSame(\App\User\Enum\Role::Admin, $result->getRole());
        $this->assertSame('Test User', $result->getName());
    }
}
