<?php

declare(strict_types=1);

namespace App\Tests\Integration\Item\MessageHandler;

use App\Item\Entity\Item;
use App\Item\Enum\ItemType;
use App\Item\Message\UpdateItem;
use App\Item\Message\CreateItem;
use App\Item\MessageHandler\UpdateItemHandler;
use App\Item\MessageHandler\CreateItemHandler;
use App\Workspace\Message\CreateWorkspace;
use App\Workspace\MessageHandler\CreateWorkspaceHandler;
use App\Tests\Integration\IntegrationTestCase;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

class UpdateItemHandlerIntegrationTest extends IntegrationTestCase
{
    private UpdateItemHandler $handler;
    private CreateItemHandler $createItemHandler;
    private CreateWorkspaceHandler $createWorkspaceHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = self::getContainer()->get(UpdateItemHandler::class);
        $this->createItemHandler = self::getContainer()->get(CreateItemHandler::class);
        $this->createWorkspaceHandler = self::getContainer()->get(CreateWorkspaceHandler::class);
    }

    public function testUpdateItemTitle(): void
    {
        $user = $this->createUser();
        $workspace = ($this->createWorkspaceHandler)(new CreateWorkspace(userId: $user->getId(), name: 'WS'));
        $item = ($this->createItemHandler)(new CreateItem(
            workspaceId: $workspace->getId(),
            type: ItemType::Note,
            title: 'Old Title',
        ));

        $result = ($this->handler)(new UpdateItem(
            itemId: $item->getId(),
            title: 'New Title',
        ));

        $this->assertSame('New Title', $result->getTitle());
    }

    public function testUpdateItemType(): void
    {
        $user = $this->createUser();
        $workspace = ($this->createWorkspaceHandler)(new CreateWorkspace(userId: $user->getId(), name: 'WS'));
        $item = ($this->createItemHandler)(new CreateItem(
            workspaceId: $workspace->getId(),
            type: ItemType::Note,
            title: 'Type Test',
        ));

        $result = ($this->handler)(new UpdateItem(
            itemId: $item->getId(),
            type: ItemType::Todo,
        ));

        $this->assertSame(ItemType::Todo, $result->getType());
    }

    public function testUpdateItemData(): void
    {
        $user = $this->createUser();
        $workspace = ($this->createWorkspaceHandler)(new CreateWorkspace(userId: $user->getId(), name: 'WS'));
        $item = ($this->createItemHandler)(new CreateItem(
            workspaceId: $workspace->getId(),
            type: ItemType::Note,
            title: 'Data Test',
            data: ['old' => 'data'],
        ));

        $result = ($this->handler)(new UpdateItem(
            itemId: $item->getId(),
            data: ['new' => 'data'],
        ));

        $this->assertSame(['new' => 'data'], $result->getData());
    }

    public function testUpdateItemSortOrder(): void
    {
        $user = $this->createUser();
        $workspace = ($this->createWorkspaceHandler)(new CreateWorkspace(userId: $user->getId(), name: 'WS'));
        $item = ($this->createItemHandler)(new CreateItem(
            workspaceId: $workspace->getId(),
            type: ItemType::Note,
            title: 'Sort Test',
            sortOrder: 1,
        ));

        $result = ($this->handler)(new UpdateItem(
            itemId: $item->getId(),
            sortOrder: 99,
        ));

        $this->assertSame(99, $result->getSortOrder());
    }

    public function testUpdateNonExistingItem(): void
    {
        $this->expectException(UnrecoverableMessageHandlingException::class);

        ($this->handler)(new UpdateItem(
            itemId: 99999,
            title: 'Ghost',
        ));
    }
}
