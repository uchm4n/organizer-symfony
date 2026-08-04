<?php

declare(strict_types=1);

namespace App\Workspace\MessageHandler;

use App\User\Entity\User;
use App\Workspace\Entity\Workspace;
use App\Workspace\Message\CreateWorkspace;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class CreateWorkspaceHandler
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function __invoke(CreateWorkspace $message): Workspace
    {
        $user = $this->em->getRepository(User::class)
            ->find($message->userId);

        $workspace = new Workspace();
        $workspace->setUser($user);
        $workspace->setName($message->name);
        $workspace->setSettings($message->settings);

        $user->setWorkspace($workspace);

        $this->em->persist($workspace);
        $this->em->flush();

        return $workspace;
    }
}
