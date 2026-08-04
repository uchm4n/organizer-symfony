<?php

declare(strict_types=1);

namespace App\Tests\Integration\Workspace\MessageHandler;

use App\Item\Entity\Item;
use App\Item\Enum\ItemType;
use App\Workspace\Message\CreateWorkspace;
use App\Workspace\MessageHandler\CreateWorkspaceHandler;
use App\Item\Message\CreateItem;
use App\Item\MessageHandler\CreateItemHandler;
use App\Workspace\Message\GetWorkspaceItems;
use App\Workspace\MessageHandler\GetWorkspaceItemsHandler;
use App\Tests\Integration\IntegrationTestCase;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

class GetWorkspaceItemsHandlerIntegrationTest extends IntegrationTestCase
{
    private CreateWorkspaceHandler $createWorkspaceHandler;
    private CreateItemHandler $createItemHandler;
    private GetWorkspaceItemsHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createWorkspaceHandler = self::getContainer()->get(CreateWorkspaceHandler::class);
        $this->createItemHandler = self::getContainer()->get(CreateItemHandler::class);
        $this->handler = self::getContainer()->get(GetWorkspaceItemsHandler::class);
    }

    public function testGetWorkspaceItems(): void
    {
        $user = $this->createUser();
        $workspace = ($this->createWorkspaceHandler)(new CreateWorkspace(userId: $user->getId(), name: 'WS'));

        ($this->createItemHandler)(new CreateItem(
            workspaceId: $workspace->getId(),
            type: ItemType::Note,
            title: 'Item 1',
        ));

        $items = ($this->handler)(new GetWorkspaceItems($workspace->getId()));

        $this->assertCount(1, $items);
        $this->assertSame('Item 1', $items[0]->getTitle());
    }

    public function testGetWorkspaceItemsEmptyWorkspace(): void
    {
        $user = $this->createUser();
        $workspace = ($this->createWorkspaceHandler)(new CreateWorkspace(userId: $user->getId(), name: 'Empty'));

        $items = ($this->handler)(new GetWorkspaceItems($workspace->getId()));

        $this->assertCount(0, $items);
    }

    public function testGetWorkspaceItemsNonExistingWorkspace(): void
    {
        $this->expectException(UnrecoverableMessageHandlingException::class);
        ($this->handler)(new GetWorkspaceItems(99999));
    }

    public function testGetWorkspaceItemsExcludesChildren(): void
    {
        $user = $this->createUser();
        $workspace = ($this->createWorkspaceHandler)(new CreateWorkspace(userId: $user->getId(), name: 'WS'));

        $parent = ($this->createItemHandler)(new CreateItem(
            workspaceId: $workspace->getId(),
            type: ItemType::Note,
            title: 'Parent',
        ));

        ($this->createItemHandler)(new CreateItem(
            workspaceId: $workspace->getId(),
            type: ItemType::Note,
            title: 'Child',
            parentId: $parent->getId(),
        ));

        $items = ($this->handler)(new GetWorkspaceItems($workspace->getId()));

        $this->assertCount(1, $items);
        $this->assertSame('Parent', $items[0]->getTitle());
    }
}
