<?php

declare(strict_types=1);

namespace App\Item\MessageHandler;

use App\Item\Entity\Item;
use App\Item\Message\CreateItem;
use App\Workspace\Entity\Workspace;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class CreateItemHandler
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function __invoke(CreateItem $message): Item
    {
        $workspace = $this->em->getRepository(Workspace::class)
            ->find($message->workspaceId);

        $parent = null;
        if ($message->parentId !== null) {
            $parent = $this->em->getRepository(Item::class)
                ->find($message->parentId);
        }

        $item = new Item();
        $item->setWorkspace($workspace);
        $item->setType($message->type);
        $item->setTitle($message->title);
        $item->setParent($parent);
        $item->setData($message->data);
        $item->setSortOrder($message->sortOrder);

        $this->em->persist($item);
        $this->em->flush();

        return $item;
    }
}
