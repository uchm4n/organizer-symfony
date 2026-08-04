<?php

declare(strict_types=1);

namespace App\User\MessageHandler;

use App\Shared\Exception\ResourceNotFoundException;
use App\User\Entity\User;
use App\User\Message\GetUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetUserHandler
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function __invoke(GetUser $message): User
    {
        $user = $this->em->getRepository(User::class)
            ->find($message->userId);

        if ($user === null) {
            throw ResourceNotFoundException::forResource('User');
        }

        return $user;
    }
}
