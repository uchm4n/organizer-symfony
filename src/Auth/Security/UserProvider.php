<?php

declare(strict_types=1);

namespace App\Auth\Security;

use App\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class UserProvider implements UserProviderInterface
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $identifier]);

        if ($user === null) {
            throw new UserNotFoundException();
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException();
        }

        $refreshed = $this->em->getRepository(User::class)
            ->find($user->getId());

        if ($refreshed === null) {
            throw new UserNotFoundException();
        }

        return $refreshed;
    }

    public function supportsClass(string $class): bool
    {
        return $class === User::class;
    }
}
