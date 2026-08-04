<?php

declare(strict_types=1);

namespace App\Tests\Functional\User;

use App\Tests\Functional\FunctionalApiTestCase;

class UserApiTest extends FunctionalApiTestCase
{
    private const EMAIL = 'user@example.com';
    private const PASSWORD = 'pass123';

    protected function setUp(): void
    {
        parent::setUp();
        $this->createUser(self::EMAIL, self::PASSWORD);
    }

    private function loginAndGetToken(): string
    {
        $this->client->request('POST', '/api/v1/login', [
            'email' => self::EMAIL,
            'password' => self::PASSWORD,
        ]);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        return $data['access_token'];
    }

    private function getJsonResponse(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true);
    }

    public function testGetUserReturnsAuthenticatedUser(): void
    {
        $token = $this->loginAndGetToken();
        $this->client->request('GET', '/api/v1/user', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        $data = $this->getJsonResponse();
        $this->assertSame(self::EMAIL, $data['email']);
    }

    public function testGetUserRequiresAuthentication(): void
    {
        $this->client->request('GET', '/api/v1/user');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetUserReturnsCorrectFields(): void
    {
        $token = $this->loginAndGetToken();
        $this->client->request('GET', '/api/v1/user', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse();
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('email', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('role', $data);
    }
}
