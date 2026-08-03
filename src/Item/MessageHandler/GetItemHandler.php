<?php

declare(strict_types=1);

namespace App\Item\MessageHandler;

use App\Item\Entity\Item;
use App\Item\Message\GetItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageException;

#[AsMessageHandler(bus: 'query.bus')]
final class GetItemHandler
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function __invoke(GetItem $message): Item
    {
        $item = $this->em->getRepository(Item::class)
            ->find($message->itemId);

        if ($item === null) {
            throw new UnrecoverableMessageException('Item not found.');
        }

        return $item;
    }
}
