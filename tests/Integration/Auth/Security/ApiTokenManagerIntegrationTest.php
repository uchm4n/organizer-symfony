<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth\Security;

use App\Auth\Security\ApiTokenManager;
use App\User\Entity\ApiToken;
use App\User\Entity\User;
use App\User\Enum\Role;
use App\Tests\Integration\IntegrationTestCase;

class ApiTokenManagerIntegrationTest extends IntegrationTestCase
{
    private ApiTokenManager $tokenManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenManager = self::getContainer()->get(ApiTokenManager::class);
    }

    public function testCreateTokenPersistsToDatabase(): void
    {
        $user = $this->createUser();
        $token = $this->tokenManager->createToken($user, 'test-token');

        $this->assertInstanceOf(ApiToken::class, $token);
        $this->assertNotNull($token->getId());
        $this->assertSame('test-token', $token->getName());
        $this->assertSame($user->getId(), $token->getUser()->getId());
    }

    public function testCreateTokenRevokesExistingTokens(): void
    {
        $user = $this->createUser();
        $this->tokenManager->createToken($user, 'first-token');
        $this->tokenManager->createToken($user, 'second-token');

        $this->em->clear();
        $tokens = $this->em->getRepository(ApiToken::class)->findBy(['user' => $user]);

        $this->assertCount(1, $tokens);
        $this->assertSame('second-token', $tokens[0]->getName());
    }

    public function testCreateTokenWithCustomTtl(): void
    {
        $user = $this->createUser();
        $token = $this->tokenManager->createToken($user, 'custom-ttl', 60);

        $expiresAt = $token->getExpiresAt();
        $this->assertNotNull($expiresAt);
        $this->assertGreaterThan(new \DateTimeImmutable('-1 minutes'), $expiresAt);
        $this->assertLessThan(new \DateTimeImmutable('+61 minutes'), $expiresAt);
    }

    public function testCreateTokenSetsPlainTextToken(): void
    {
        $user = $this->createUser();
        $token = $this->tokenManager->createToken($user, 'plain-text');

        $plainText = $token->getPlainTextToken();
        $this->assertNotNull($plainText);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $plainText);
    }

    public function testCheckPasswordWithValidPassword(): void
    {
        $user = $this->createUser(password: 'my-secret');

        $this->assertTrue($this->tokenManager->checkPassword($user, 'my-secret'));
    }

    public function testCheckPasswordWithInvalidPassword(): void
    {
        $user = $this->createUser(password: 'my-secret');

        $this->assertFalse($this->tokenManager->checkPassword($user, 'wrong-password'));
    }

    public function testFindValidTokenWithNonExpiredToken(): void
    {
        $user = $this->createUser();
        $token = $this->tokenManager->createToken($user, 'findable');
        $hashedToken = hash('sha256', $token->getPlainTextToken());

        $found = $this->tokenManager->findValidToken($hashedToken);

        $this->assertNotNull($found);
        $this->assertSame($token->getId(), $found->getId());
    }

    public function testFindValidTokenWithExpiredToken(): void
    {
        $user = $this->createUser();
        $token = $this->tokenManager->createToken($user, 'expired', -1);
        $hashedToken = hash('sha256', $token->getPlainTextToken());

        $found = $this->tokenManager->findValidToken($hashedToken);

        $this->assertNull($found);
    }

    public function testFindValidTokenWithNonExistentToken(): void
    {
        $found = $this->tokenManager->findValidToken('non-existent-hash');

        $this->assertNull($found);
    }
}
