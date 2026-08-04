<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth\Security;

use App\Auth\Security\UserProvider;
use App\User\Entity\User;
use App\Tests\Integration\IntegrationTestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

class UserProviderIntegrationTest extends IntegrationTestCase
{
    private UserProvider $userProvider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userProvider = self::getContainer()->get(UserProvider::class);
    }

    public function testLoadUserByIdentifierWithValidEmail(): void
    {
        $created = $this->createUser(email: 'load@example.com');

        $found = $this->userProvider->loadUserByIdentifier('load@example.com');

        $this->assertInstanceOf(User::class, $found);
        $this->assertSame($created->getId(), $found->getId());
    }

    public function testLoadUserByIdentifierWithUnknownEmail(): void
    {
        $this->expectException(UserNotFoundException::class);
        $this->userProvider->loadUserByIdentifier('nonexistent@example.com');
    }

    public function testRefreshUserWithValidUser(): void
    {
        $user = $this->createUser();
        $this->em->clear();

        $refreshed = $this->userProvider->refreshUser($user);

        $this->assertInstanceOf(User::class, $refreshed);
        $this->assertSame($user->getId(), $refreshed->getId());
    }

    public function testRefreshUserWithNonUserInstance(): void
    {
        $this->expectException(\TypeError::class);

        $this->userProvider->refreshUser(new \stdClass());
    }

    public function testSupportsClassWithUserClass(): void
    {
        $this->assertTrue($this->userProvider->supportsClass(User::class));
    }

    public function testSupportsClassWithOtherClass(): void
    {
        $this->assertFalse($this->userProvider->supportsClass(\stdClass::class));
    }
}
