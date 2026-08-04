<?php

declare(strict_types=1);

namespace App\Tests\Integration\Workspace\MessageHandler;

use App\Workspace\Entity\Workspace;
use App\Workspace\Message\CreateWorkspace;
use App\Workspace\MessageHandler\CreateWorkspaceHandler;
use App\Workspace\Message\UpdateWorkspace;
use App\Workspace\MessageHandler\UpdateWorkspaceHandler;
use App\Tests\Integration\IntegrationTestCase;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

class UpdateWorkspaceHandlerIntegrationTest extends IntegrationTestCase
{
    private CreateWorkspaceHandler $createHandler;
    private UpdateWorkspaceHandler $updateHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createHandler = self::getContainer()->get(CreateWorkspaceHandler::class);
        $this->updateHandler = self::getContainer()->get(UpdateWorkspaceHandler::class);
    }

    public function testUpdateWorkspaceName(): void
    {
        $user = $this->createUser();
        $workspace = ($this->createHandler)(new CreateWorkspace(userId: $user->getId(), name: 'Old Name'));

        $result = ($this->updateHandler)(new UpdateWorkspace(
            workspaceId: $workspace->getId(),
            name: 'New Name',
        ));

        $this->assertSame('New Name', $result->getName());
    }

    public function testUpdateWorkspaceSettings(): void
    {
        $user = $this->createUser();
        $workspace = ($this->createHandler)(new CreateWorkspace(userId: $user->getId(), name: 'WS'));

        $newSettings = ['key' => 'value'];
        $result = ($this->updateHandler)(new UpdateWorkspace(
            workspaceId: $workspace->getId(),
            name: 'WS',
            settings: $newSettings,
        ));

        $this->assertSame($newSettings, $result->getSettings());
    }

    public function testUpdateNonExistingWorkspace(): void
    {
        $this->expectException(UnrecoverableMessageHandlingException::class);

        ($this->updateHandler)(new UpdateWorkspace(
            workspaceId: 99999,
            name: 'Ghost',
        ));
    }

    public function testUpdateWorkspaceWithNullSettings(): void
    {
        $user = $this->createUser();
        $workspace = ($this->createHandler)(new CreateWorkspace(
            userId: $user->getId(),
            name: 'WS',
            settings: ['old' => 'data'],
        ));

        $result = ($this->updateHandler)(new UpdateWorkspace(
            workspaceId: $workspace->getId(),
            name: 'WS',
            settings: null,
        ));

        $this->assertNull($result->getSettings());
    }
}
