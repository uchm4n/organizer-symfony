<?php

declare(strict_types=1);

namespace App\Tests\Integration\Item\MessageHandler;

use App\Item\Entity\Item;
use App\Item\Enum\ItemType;
use App\Item\Message\GetItem;
use App\Item\Message\CreateItem;
use App\Item\MessageHandler\GetItemHandler;
use App\Item\MessageHandler\CreateItemHandler;
use App\Workspace\Message\CreateWorkspace;
use App\Workspace\MessageHandler\CreateWorkspaceHandler;
use App\Tests\Integration\IntegrationTestCase;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

class GetItemHandlerIntegrationTest extends IntegrationTestCase
{
    private GetItemHandler $handler;
    private CreateItemHandler $createItemHandler;
    private CreateWorkspaceHandler $createWorkspaceHandler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = self::getContainer()->get(GetItemHandler::class);
        $this->createItemHandler = self::getContainer()->get(CreateItemHandler::class);
        $this->createWorkspaceHandler = self::getContainer()->get(CreateWorkspaceHandler::class);
    }

    public function testGetExistingItem(): void
    {
        $user = $this->createUser();
        $workspace = ($this->createWorkspaceHandler)(new CreateWorkspace(userId: $user->getId(), name: 'WS'));
        $item = ($this->createItemHandler)(new CreateItem(
            workspaceId: $workspace->getId(),
            type: ItemType::Note,
            title: 'Findable',
        ));

        $result = ($this->handler)(new GetItem($item->getId()));

        $this->assertInstanceOf(Item::class, $result);
        $this->assertSame('Findable', $result->getTitle());
    }

    public function testGetNonExistingItem(): void
    {
        $this->expectException(UnrecoverableMessageHandlingException::class);
        ($this->handler)(new GetItem(99999));
    }

    public function testGetItemReturnsCorrectData(): void
    {
        $user = $this->createUser();
        $workspace = ($this->createWorkspaceHandler)(new CreateWorkspace(userId: $user->getId(), name: 'WS'));
        $item = ($this->createItemHandler)(new CreateItem(
            workspaceId: $workspace->getId(),
            type: ItemType::Todo,
            title: 'Data Check',
            data: ['key' => 'value'],
            sortOrder: 3,
        ));

        $result = ($this->handler)(new GetItem($item->getId()));

        $this->assertSame(ItemType::Todo, $result->getType());
        $this->assertSame('Data Check', $result->getTitle());
        $this->assertSame(['key' => 'value'], $result->getData());
        $this->assertSame(3, $result->getSortOrder());
    }
}
