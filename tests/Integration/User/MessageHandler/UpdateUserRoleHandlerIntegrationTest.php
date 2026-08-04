<?php

declare(strict_types=1);

namespace App\Tests\Integration\User\MessageHandler;

use App\User\Entity\User;
use App\User\Enum\Role;
use App\User\Message\UpdateUserRole;
use App\User\MessageHandler\UpdateUserRoleHandler;
use App\Tests\Integration\IntegrationTestCase;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

class UpdateUserRoleHandlerIntegrationTest extends IntegrationTestCase
{
    private UpdateUserRoleHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = self::getContainer()->get(UpdateUserRoleHandler::class);
    }

    public function testUpdateUserRole(): void
    {
        $user = $this->createUser(role: Role::User);

        $result = ($this->handler)(new UpdateUserRole($user->getId(), Role::Admin));

        $this->assertInstanceOf(User::class, $result);
        $this->assertSame(Role::Admin, $result->getRole());
    }

    public function testUpdateNonExistingUser(): void
    {
        $this->expectException(UnrecoverableMessageHandlingException::class);

        ($this->handler)(new UpdateUserRole(99999, Role::Admin));
    }

    public function testUpdateUserRoleFromUserToAdmin(): void
    {
        $user = $this->createUser(role: Role::User);
        $this->assertSame(Role::User, $user->getRole());

        ($this->handler)(new UpdateUserRole($user->getId(), Role::Admin));

        $this->em->clear();
        $refreshed = $this->em->getRepository(User::class)->find($user->getId());
        $this->assertSame(Role::Admin, $refreshed->getRole());
    }

    public function testUpdateUserRoleFromAdminToUser(): void
    {
        $user = $this->createUser(role: Role::Admin);
        $this->assertSame(Role::Admin, $user->getRole());

        ($this->handler)(new UpdateUserRole($user->getId(), Role::User));

        $this->em->clear();
        $refreshed = $this->em->getRepository(User::class)->find($user->getId());
        $this->assertSame(Role::User, $refreshed->getRole());
    }
}
