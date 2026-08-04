<?php

declare(strict_types=1);

namespace App\Item\MessageHandler;

use App\Item\Entity\Item;
use App\Item\Message\UpdateItem;
use App\Shared\Exception\ResourceNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UpdateItemHandler
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function __invoke(UpdateItem $message): Item
    {
        $item = $this->em->getRepository(Item::class)
            ->find($message->itemId);

        if ($item === null) {
            throw ResourceNotFoundException::forResource('Item');
        }

        if ($message->title !== null) {
            $item->setTitle($message->title);
        }
        if ($message->type !== null) {
            $item->setType($message->type);
        }
        if ($message->data !== null) {
            $item->setData($message->data);
        }
        if ($message->sortOrder !== null) {
            $item->setSortOrder($message->sortOrder);
        }

        $this->em->flush();

        return $item;
    }
}
