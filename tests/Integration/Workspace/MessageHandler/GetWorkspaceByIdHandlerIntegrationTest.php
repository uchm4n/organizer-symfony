<?php

declare(strict_types=1);

namespace App\Tests\Integration\Workspace\MessageHandler;

use App\Tests\Integration\IntegrationTestCase;
use App\Workspace\Entity\Workspace;
use App\Workspace\Message\CreateWorkspace;
use App\Workspace\Message\GetWorkspaceById;
use App\Workspace\MessageHandler\CreateWorkspaceHandler;
use App\Workspace\MessageHandler\GetWorkspaceByIdHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

class GetWorkspaceByIdHandlerIntegrationTest extends IntegrationTestCase
{
    private GetWorkspaceByIdHandler $handler;
    private CreateWorkspaceHandler $createHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = self::getContainer()->get(GetWorkspaceByIdHandler::class);
        $this->createHandler = self::getContainer()->get(CreateWorkspaceHandler::class);
    }

    public function testGetExistingWorkspaceById(): void
    {
        $user = $this->createUser();
        $workspace = ($this->createHandler)(new CreateWorkspace(userId: $user->getId(), name: 'Team Space'));

        $result = ($this->handler)(new GetWorkspaceById($workspace->getId()));

        $this->assertInstanceOf(Workspace::class, $result);
        $this->assertSame($workspace->getId(), $result->getId());
        $this->assertSame('Team Space', $result->getName());
    }

    public function testGetWorkspaceByIdForNonExistingWorkspace(): void
    {
        $this->expectException(UnrecoverableMessageHandlingException::class);
        ($this->handler)(new GetWorkspaceById(99999));
    }
}
