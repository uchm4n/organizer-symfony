<?php

declare(strict_types=1);

namespace App\Tests\Integration\Workspace\MessageHandler;

use App\Workspace\Entity\Workspace;
use App\Workspace\Message\GetWorkspace;
use App\Workspace\MessageHandler\GetWorkspaceHandler;
use App\Workspace\Message\CreateWorkspace;
use App\Workspace\MessageHandler\CreateWorkspaceHandler;
use App\Tests\Integration\IntegrationTestCase;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

class GetWorkspaceHandlerIntegrationTest extends IntegrationTestCase
{
    private GetWorkspaceHandler $handler;
    private CreateWorkspaceHandler $createHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = self::getContainer()->get(GetWorkspaceHandler::class);
        $this->createHandler = self::getContainer()->get(CreateWorkspaceHandler::class);
    }

    public function testGetExistingWorkspace(): void
    {
        $user = $this->createUser();
        ($this->createHandler)(new CreateWorkspace(userId: $user->getId(), name: 'Test'));

        $result = ($this->handler)(new GetWorkspace($user->getId()));

        $this->assertInstanceOf(Workspace::class, $result);
        $this->assertSame('Test', $result->getName());
    }

    public function testGetWorkspaceForUserWithoutWorkspace(): void
    {
        $user = $this->createUser();

        $this->expectException(UnrecoverableMessageHandlingException::class);
        ($this->handler)(new GetWorkspace($user->getId()));
    }

    public function testGetWorkspaceForNonExistingUser(): void
    {
        $this->expectException(UnrecoverableMessageHandlingException::class);
        ($this->handler)(new GetWorkspace(99999));
    }
}
