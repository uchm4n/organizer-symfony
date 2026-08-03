<?php

declare(strict_types=1);

namespace App\Tests\Auth\MessageHandler;

use App\Auth\Exception\InvalidCredentialsException;
use App\Auth\Message\LoginUser;
use App\Auth\MessageHandler\LoginUserHandler;
use App\Auth\Security\ApiTokenManager;
use App\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class LoginUserHandlerTest extends TestCase
{
    private $em;
    private $tokenManager;
    private LoginUserHandler $handler;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->tokenManager = $this->createMock(ApiTokenManager::class);
        $this->handler = new LoginUserHandler($this->em, $this->tokenManager);
    }

    public function testLoginWithValidCredentials(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('hashed_password');

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'test@example.com'])
            ->willReturn($user);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);

        $this->tokenManager->expects($this->once())
            ->method('checkPassword')
            ->with($user, 'password')
            ->willReturn(true);

        $token = $this->createMock(\App\User\Entity\ApiToken::class);
        $this->tokenManager->expects($this->once())
            ->method('createToken')
            ->with($user, 'api-token')
            ->willReturn($token);

        $result = ($this->handler)(new LoginUser('test@example.com', 'password'));

        $this->assertSame($token, $result);
    }

    public function testLoginWithInvalidCredentials(): void
    {
        $this->expectException(InvalidCredentialsException::class);

        $repository = $this->createMock(EntityRepository::class);
        $repository->expects($this->once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(User::class)
            ->willReturn($repository);

        ($this->handler)(new LoginUser('wrong@example.com', 'wrong'));
    }
}
