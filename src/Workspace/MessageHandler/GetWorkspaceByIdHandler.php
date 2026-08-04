<?php

declare(strict_types=1);

namespace App\Workspace\MessageHandler;

use App\Shared\Exception\ResourceNotFoundException;
use App\Workspace\Entity\Workspace;
use App\Workspace\Message\GetWorkspaceById;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetWorkspaceByIdHandler
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function __invoke(GetWorkspaceById $message): Workspace
    {
        $workspace = $this->em->getRepository(Workspace::class)
            ->find($message->workspaceId);

        if ($workspace === null) {
            throw ResourceNotFoundException::forResource('Workspace');
        }

        return $workspace;
    }
}
