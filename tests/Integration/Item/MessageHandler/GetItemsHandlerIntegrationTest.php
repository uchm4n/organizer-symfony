<?php

declare(strict_types=1);

namespace App\Tests\Integration\Item\MessageHandler;

use App\Item\Enum\ItemType;
use App\Item\Message\GetItems;
use App\Item\Message\CreateItem;
use App\Item\MessageHandler\GetItemsHandler;
use App\Item\MessageHandler\CreateItemHandler;
use App\Workspace\Message\CreateWorkspace;
use App\Workspace\MessageHandler\CreateWorkspaceHandler;
use App\Tests\Integration\IntegrationTestCase;

class GetItemsHandlerIntegrationTest extends IntegrationTestCase
{
    private GetItemsHandler $handler;
    private CreateItemHandler $createItemHandler;
    private CreateWorkspaceHandler $createWorkspaceHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = self::getContainer()->get(GetItemsHandler::class);
        $this->createItemHandler = self::getContainer()->get(CreateItemHandler::class);
        $this->createWorkspaceHandler = self::getContainer()->get(CreateWorkspaceHandler::class);
    }

    public function testGetItemsByWorkspace(): void
    {
        $user = $this->createUser();
        $workspace = ($this->createWorkspaceHandler)(new CreateWorkspace(userId: $user->getId(), name: 'WS'));

        ($this->createItemHandler)(new CreateItem(
            workspaceId: $workspace->getId(),
            type: ItemType::Note,
            title: 'Item 1',
        ));

        ($this->createItemHandler)(new CreateItem(
            workspaceId: $workspace->getId(),
            type: ItemType::Todo,
            title: 'Item 2',
        ));

        $items = ($this->handler)(new GetItems($workspace->getId()));

        $this->assertCount(2, $items);
    }

    public function testGetItemsByParent(): void
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
            title: 'Child 1',
            parentId: $parent->getId(),
        ));

        ($this->createItemHandler)(new CreateItem(
            workspaceId: $workspace->getId(),
            type: ItemType::Note,
            title: 'Child 2',
            parentId: $parent->getId(),
        ));

        $items = ($this->handler)(new GetItems($workspace->getId(), parentId: $parent->getId()));

        $this->assertCount(2, $items);
    }

    public function testGetItemsEmptyWorkspace(): void
    {
        $user = $this->createUser();
        $workspace = ($this->createWorkspaceHandler)(new CreateWorkspace(userId: $user->getId(), name: 'Empty'));

        $items = ($this->handler)(new GetItems($workspace->getId()));

        $this->assertCount(0, $items);
    }

    public function testGetItemsOrdering(): void
    {
        $user = $this->createUser();
        $workspace = ($this->createWorkspaceHandler)(new CreateWorkspace(userId: $user->getId(), name: 'WS'));

        ($this->createItemHandler)(new CreateItem(
            workspaceId: $workspace->getId(),
            type: ItemType::Note,
            title: 'C',
            sortOrder: 30,
        ));

        ($this->createItemHandler)(new CreateItem(
            workspaceId: $workspace->getId(),
            type: ItemType::Note,
            title: 'A',
            sortOrder: 10,
        ));

        ($this->createItemHandler)(new CreateItem(
            workspaceId: $workspace->getId(),
            type: ItemType::Note,
            title: 'B',
            sortOrder: 20,
        ));

        $items = ($this->handler)(new GetItems($workspace->getId()));

        $titles = array_map(fn($i) => $i->getTitle(), $items);
        $this->assertSame(['A', 'B', 'C'], $titles);
    }

    public function testGetItemsOnlyTopLevel(): void
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

        $items = ($this->handler)(new GetItems($workspace->getId()));

        $this->assertCount(1, $items);
        $this->assertSame('Parent', $items[0]->getTitle());
    }

    public function testGetItemsAcrossWorkspaces(): void
    {
        $user1 = $this->createUser(email: 'user1@example.com');
        $user2 = $this->createUser(email: 'user2@example.com');
        $ws1 = ($this->createWorkspaceHandler)(new CreateWorkspace(userId: $user1->getId(), name: 'WS1'));
        $ws2 = ($this->createWorkspaceHandler)(new CreateWorkspace(userId: $user2->getId(), name: 'WS2'));

        ($this->createItemHandler)(new CreateItem(
            workspaceId: $ws1->getId(),
            type: ItemType::Note,
            title: 'WS1 Item',
        ));

        ($this->createItemHandler)(new CreateItem(
            workspaceId: $ws2->getId(),
            type: ItemType::Note,
            title: 'WS2 Item',
        ));

        $items1 = ($this->handler)(new GetItems($ws1->getId()));
        $items2 = ($this->handler)(new GetItems($ws2->getId()));

        $this->assertCount(1, $items1);
        $this->assertCount(1, $items2);
        $this->assertSame('WS1 Item', $items1[0]->getTitle());
        $this->assertSame('WS2 Item', $items2[0]->getTitle());
    }
}
