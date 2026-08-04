<?php

declare(strict_types=1);

namespace App\Workspace\MessageHandler;

use App\Shared\Exception\ResourceNotFoundException;
use App\Workspace\Entity\Workspace;
use App\Workspace\Message\UpdateWorkspace;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UpdateWorkspaceHandler
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function __invoke(UpdateWorkspace $message): Workspace
    {
        $workspace = $this->em->getRepository(Workspace::class)
            ->find($message->workspaceId);

        if ($workspace === null) {
            throw ResourceNotFoundException::forResource('Workspace');
        }

        $workspace->setName($message->name);
        $workspace->setSettings($message->settings);

        $this->em->flush();

        return $workspace;
    }
}
