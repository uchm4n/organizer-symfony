<?php

declare(strict_types=1);

namespace App\Item\MessageHandler;

use App\Item\Entity\Item;
use App\Item\Message\GetItems;
use App\Workspace\Entity\Workspace;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetItemsHandler
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function __invoke(GetItems $message): array
    {
        $criteria = ['workspace' => $message->workspaceId];
        if ($message->parentId !== null) {
            $criteria['parent'] = $message->parentId;
        }

        return $this->em->getRepository(Item::class)
            ->findBy($criteria, ['sortOrder' => 'ASC']);
    }
}
