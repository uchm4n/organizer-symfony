<?php

declare(strict_types=1);

namespace App\Auth\MessageHandler;

use App\Auth\Exception\InvalidCredentialsException;
use App\Auth\Message\LoginUser;
use App\Auth\Security\ApiTokenManager;
use App\User\Entity\ApiToken;
use App\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class LoginUserHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private ApiTokenManager $tokenManager,
    ) {}

    public function __invoke(LoginUser $message): ApiToken
    {
        $user = $this->em->getRepository(User::class)
            ->findOneBy(['email' => $message->email]);

        if ($user === null || !$this->tokenManager->checkPassword($user, $message->password)) {
            throw new InvalidCredentialsException();
        }

        return $this->tokenManager->createToken($user, 'api-token');
    }
}
