<?php

declare(strict_types=1);

namespace App\Workspace\MessageHandler;

use App\Item\Entity\Item;
use App\Workspace\Entity\Workspace;
use App\Workspace\Message\GetWorkspaceItems;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageException;

#[AsMessageHandler(bus: 'query.bus')]
final class GetWorkspaceItemsHandler
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function __invoke(GetWorkspaceItems $message): array
    {
        $workspace = $this->em->getRepository(Workspace::class)
            ->find($message->workspaceId);

        if ($workspace === null) {
            throw new UnrecoverableMessageException('Workspace not found.');
        }

        return $this->em->getRepository(Item::class)
            ->findBy(['workspace' => $workspace, 'parent' => null], ['sortOrder' => 'ASC']);
    }
}
