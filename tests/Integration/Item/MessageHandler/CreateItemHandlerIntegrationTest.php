<?php

declare(strict_types=1);

namespace App\Tests\Integration\Item\MessageHandler;

use App\Item\Entity\Item;
use App\Item\Enum\ItemType;
use App\Item\Message\CreateItem;
use App\Item\MessageHandler\CreateItemHandler;
use App\Workspace\Message\CreateWorkspace;
use App\Workspace\MessageHandler\CreateWorkspaceHandler;
use App\Tests\Integration\IntegrationTestCase;

class CreateItemHandlerIntegrationTest extends IntegrationTestCase
{
    private CreateWorkspaceHandler $createWorkspaceHandler;
    private CreateItemHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createWorkspaceHandler = self::getContainer()->get(CreateWorkspaceHandler::class);
        $this->handler = self::getContainer()->get(CreateItemHandler::class);
    }

    public function testCreateItemWithoutParent(): void
    {
        $user = $this->createUser();
        $workspace = ($this->createWorkspaceHandler)(new CreateWorkspace(userId: $user->getId(), name: 'WS'));

        $item = ($this->handler)(new CreateItem(
            workspaceId: $workspace->getId(),
            type: ItemType::Note,
            title: 'My Note',
        ));

        $this->assertInstanceOf(Item::class, $item);
        $this->assertNotNull($item->getId());
        $this->assertSame('My Note', $item->getTitle());
        $this->assertNull($item->getParent());
    }

    public function testCreateItemWithParent(): void
    {
        $user = $this->createUser();
        $workspace = ($this->createWorkspaceHandler)(new CreateWorkspace(userId: $user->getId(), name: 'WS'));

        $parent = ($this->handler)(new CreateItem(
            workspaceId: $workspace->getId(),
            type: ItemType::Note,
            title: 'Parent',
        ));

        $child = ($this->handler)(new CreateItem(
            workspaceId: $workspace->getId(),
            type: ItemType::Note,
            title: 'Child',
            parentId: $parent->getId(),
        ));

        $this->assertNotNull($child->getParent());
        $this->assertSame($parent->getId(), $child->getParent()->getId());
    }

    public function testCreateItemWithAllFields(): void
    {
        $user = $this->createUser();
        $workspace = ($this->createWorkspaceHandler)(new CreateWorkspace(userId: $user->getId(), name: 'WS'));

        $item = ($this->handler)(new CreateItem(
            workspaceId: $workspace->getId(),
            type: ItemType::Todo,
            title: 'Full Item',
            data: ['completed' => false, 'priority' => 'high'],
            sortOrder: 5,
        ));

        $this->assertSame(ItemType::Todo, $item->getType());
        $this->assertSame('Full Item', $item->getTitle());
        $this->assertSame(['completed' => false, 'priority' => 'high'], $item->getData());
        $this->assertSame(5, $item->getSortOrder());
    }

    public function testCreateItemWithNullData(): void
    {
        $user = $this->createUser();
        $workspace = ($this->createWorkspaceHandler)(new CreateWorkspace(userId: $user->getId(), name: 'WS'));

        $item = ($this->handler)(new CreateItem(
            workspaceId: $workspace->getId(),
            type: ItemType::Note,
            title: 'No Data',
            data: null,
        ));

        $this->assertNull($item->getData());
    }

    public function testCreateItemLinkedToWorkspace(): void
    {
        $user = $this->createUser();
        $workspace = ($this->createWorkspaceHandler)(new CreateWorkspace(userId: $user->getId(), name: 'WS'));

        $item = ($this->handler)(new CreateItem(
            workspaceId: $workspace->getId(),
            type: ItemType::Note,
            title: 'Linked',
        ));

        $this->assertSame($workspace->getId(), $item->getWorkspace()->getId());
    }
}
