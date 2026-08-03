<?php

declare(strict_types=1);

namespace App\Auth\Security;

use App\User\Entity\ApiToken;
use App\User\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ApiTokenManager
{
    private const TOKEN_TTL_MINUTES = 2880; // 2 days

    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
    ) {}

    public function createToken(User $user, string $name, ?int $ttlMinutes = null): ApiToken
    {
        $ttlMinutes ??= self::TOKEN_TTL_MINUTES;

        // Revoke existing tokens
        foreach ($user->getApiTokens() as $existing) {
            $this->em->remove($existing);
        }

        $plainToken = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $plainToken);

        $token = new ApiToken(
            user: $user,
            name: $name,
            token: $hashedToken,
            expiresAt: new \DateTimeImmutable("+{$ttlMinutes} minutes"),
        );

        $this->em->persist($token);
        $this->em->flush();

        $token->setPlainTextToken($plainToken);
        return $token;
    }

    public function checkPassword(User $user, string $plainPassword): bool
    {
        return $this->passwordHasher->isPasswordValid($user, $plainPassword);
    }

    public function findValidToken(string $hashedToken): ?ApiToken
    {
        $token = $this->em->getRepository(ApiToken::class)
            ->findOneBy(['token' => $hashedToken]);

        if ($token === null || $token->isExpired()) {
            return null;
        }

        return $token;
    }
}
