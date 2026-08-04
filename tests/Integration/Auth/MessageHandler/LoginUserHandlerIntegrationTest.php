<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth\MessageHandler;

use App\Auth\Exception\InvalidCredentialsException;
use App\Auth\Message\LoginUser;
use App\Auth\MessageHandler\LoginUserHandler;
use App\User\Entity\ApiToken;
use App\User\Entity\User;
use App\Tests\Integration\IntegrationTestCase;

class LoginUserHandlerIntegrationTest extends IntegrationTestCase
{
    private LoginUserHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = self::getContainer()->get(LoginUserHandler::class);
    }

    public function testLoginWithValidCredentials(): void
    {
        $user = $this->createUser(email: 'login@example.com', password: 'correct-password');

        $result = ($this->handler)(new LoginUser('login@example.com', 'correct-password'));

        $this->assertInstanceOf(ApiToken::class, $result);
        $this->assertNotNull($result->getPlainTextToken());
        $this->assertSame($user->getId(), $result->getUser()->getId());
    }

    public function testLoginWithInvalidEmail(): void
    {
        $this->expectException(InvalidCredentialsException::class);

        ($this->handler)(new LoginUser('nonexistent@example.com', 'password'));
    }

    public function testLoginWithInvalidPassword(): void
    {
        $this->createUser(email: 'user@example.com', password: 'correct-password');

        $this->expectException(InvalidCredentialsException::class);

        ($this->handler)(new LoginUser('user@example.com', 'wrong-password'));
    }

    public function testLoginCreatesTokenWithCorrectUser(): void
    {
        $user = $this->createUser(email: 'specific@example.com', password: 'pass');

        $token = ($this->handler)(new LoginUser('specific@example.com', 'pass'));

        $this->em->clear();
        $found = $this->em->getRepository(User::class)->find($user->getId());
        $this->assertNotNull($found);
        $this->assertCount(1, $found->getApiTokens()->toArray());
    }
}
