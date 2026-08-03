<?php

declare(strict_types=1);

namespace App\Workspace\MessageHandler;

use App\User\Entity\User;
use App\Workspace\Entity\Workspace;
use App\Workspace\Message\GetWorkspace;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageException;

#[AsMessageHandler(bus: 'query.bus')]
final class GetWorkspaceHandler
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function __invoke(GetWorkspace $message): Workspace
    {
        $user = $this->em->getRepository(User::class)
            ->find($message->userId);

        if ($user === null || $user->getWorkspace() === null) {
            throw new UnrecoverableMessageException('Workspace not found.');
        }

        return $user->getWorkspace();
    }
}
