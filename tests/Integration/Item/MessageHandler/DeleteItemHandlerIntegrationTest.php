<?php

declare(strict_types=1);

namespace App\Tests\Integration\Item\MessageHandler;

use App\Item\Entity\Item;
use App\Item\Enum\ItemType;
use App\Item\Message\DeleteItem;
use App\Item\Message\CreateItem;
use App\Item\MessageHandler\DeleteItemHandler;
use App\Item\MessageHandler\CreateItemHandler;
use App\Workspace\Message\CreateWorkspace;
use App\Workspace\MessageHandler\CreateWorkspaceHandler;
use App\Tests\Integration\IntegrationTestCase;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

class DeleteItemHandlerIntegrationTest extends IntegrationTestCase
{
    private DeleteItemHandler $handler;
    private CreateItemHandler $createItemHandler;
    private CreateWorkspaceHandler $createWorkspaceHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = self::getContainer()->get(DeleteItemHandler::class);
        $this->createItemHandler = self::getContainer()->get(CreateItemHandler::class);
        $this->createWorkspaceHandler = self::getContainer()->get(CreateWorkspaceHandler::class);
    }

    public function testDeleteExistingItem(): void
    {
        $user = $this->createUser();
        $workspace = ($this->createWorkspaceHandler)(new CreateWorkspace(userId: $user->getId(), name: 'WS'));
        $item = ($this->createItemHandler)(new CreateItem(
            workspaceId: $workspace->getId(),
            type: ItemType::Note,
            title: 'To Delete',
        ));
        $itemId = $item->getId();

        ($this->handler)(new DeleteItem($itemId));

        $found = $this->em->getRepository(Item::class)->find($itemId);
        $this->assertNull($found);
    }

    public function testDeleteNonExistingItem(): void
    {
        $this->expectException(UnrecoverableMessageHandlingException::class);

        ($this->handler)(new DeleteItem(99999));
    }

    public function testDeleteItemChildrenBecomeOrphaned(): void
    {
        $user = $this->createUser();
        $workspace = ($this->createWorkspaceHandler)(new CreateWorkspace(userId: $user->getId(), name: 'WS'));

        $parent = ($this->createItemHandler)(new CreateItem(
            workspaceId: $workspace->getId(),
            type: ItemType::Note,
            title: 'Parent',
        ));

        $child = ($this->createItemHandler)(new CreateItem(
            workspaceId: $workspace->getId(),
            type: ItemType::Note,
            title: 'Child',
            parentId: $parent->getId(),
        ));

        ($this->handler)(new DeleteItem($parent->getId()));

        $foundChild = $this->em->getRepository(Item::class)->find($child->getId());
        $this->assertNotNull($foundChild);
    }
}
