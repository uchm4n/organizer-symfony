<?php

declare(strict_types=1);

namespace App\Tests\Integration\Workspace\MessageHandler;

use App\Workspace\Entity\Workspace;
use App\Workspace\Message\CreateWorkspace;
use App\Workspace\MessageHandler\CreateWorkspaceHandler;
use App\Tests\Integration\IntegrationTestCase;

class CreateWorkspaceHandlerIntegrationTest extends IntegrationTestCase
{
    private CreateWorkspaceHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = self::getContainer()->get(CreateWorkspaceHandler::class);
    }

    public function testCreateWorkspace(): void
    {
        $user = $this->createUser();

        $workspace = ($this->handler)(new CreateWorkspace(
            userId: $user->getId(),
            name: 'My Workspace',
        ));

        $this->assertInstanceOf(Workspace::class, $workspace);
        $this->assertNotNull($workspace->getId());
        $this->assertSame('My Workspace', $workspace->getName());
    }

    public function testCreateWorkspaceWithNullSettings(): void
    {
        $user = $this->createUser();

        $workspace = ($this->handler)(new CreateWorkspace(
            userId: $user->getId(),
            name: 'No Settings',
            settings: null,
        ));

        $this->assertNull($workspace->getSettings());
    }

    public function testCreateWorkspaceLinkedToUser(): void
    {
        $user = $this->createUser();

        $workspace = ($this->handler)(new CreateWorkspace(
            userId: $user->getId(),
            name: 'Linked Workspace',
        ));

        $this->assertSame($user->getId(), $workspace->getUser()->getId());
    }

    public function testCreateWorkspaceWithNameAndSettings(): void
    {
        $user = $this->createUser();
        $settings = ['theme' => 'dark', 'language' => 'en'];

        $workspace = ($this->handler)(new CreateWorkspace(
            userId: $user->getId(),
            name: 'Full Workspace',
            settings: $settings,
        ));

        $this->assertSame($settings, $workspace->getSettings());
    }
}
