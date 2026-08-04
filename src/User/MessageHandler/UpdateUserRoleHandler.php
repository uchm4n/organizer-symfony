<?php

declare(strict_types=1);

namespace App\User\MessageHandler;

use App\Shared\Exception\ResourceNotFoundException;
use App\User\Entity\User;
use App\User\Message\UpdateUserRole;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UpdateUserRoleHandler
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function __invoke(UpdateUserRole $message): User
    {
        $user = $this->em->getRepository(User::class)
            ->find($message->userId);

        if ($user === null) {
            throw ResourceNotFoundException::forResource('User');
        }

        $user->setRole($message->role);
        $this->em->flush();

        return $user;
    }
}
