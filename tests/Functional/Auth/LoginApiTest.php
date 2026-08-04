<?php

declare(strict_types=1);

namespace App\Tests\Functional\Auth;

use App\Tests\Functional\FunctionalApiTestCase;

class LoginApiTest extends FunctionalApiTestCase
{
    private const EMAIL = 'test@example.com';
    private const PASSWORD = 'secret123';
    private const INVALID_PASSWORD = 'wrong';

    protected function setUp(): void
    {
        parent::setUp();
        $this->createUser(self::EMAIL, self::PASSWORD);
    }

    private function loginRequest(string $email, string $password): void
    {
        $this->client->request('POST', '/api/v1/login', [
            'email' => $email,
            'password' => $password,
        ]);
    }

    private function getJsonResponse(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true);
    }

    public function testLoginReturnsBearerToken(): void
    {
        $this->loginRequest(self::EMAIL, self::PASSWORD);

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);

        $data = $this->getJsonResponse();
        $this->assertArrayHasKey('access_token', $data);
        $this->assertNotEmpty($data['access_token']);
        $this->assertSame('Bearer', $data['token_type']);
    }

    public function testLoginWithInvalidCredentialsReturns401(): void
    {
        $this->loginRequest(self::EMAIL, self::INVALID_PASSWORD);

        $this->assertResponseStatusCodeSame(401);
        $this->assertResponseHeaderSame('content-type', 'application/problem+json');
    }

    public function testLoginWithMissingFieldsReturns400(): void
    {
        $this->client->request('POST', '/api/v1/login', []);

        $this->assertResponseStatusCodeSame(400);
    }

    public function testLoginTokenCanAccessProtectedEndpoint(): void
    {
        $this->loginRequest(self::EMAIL, self::PASSWORD);
        $data = $this->getJsonResponse();
        $token = $data['access_token'];

        $this->client->request('GET', '/api/v1/user', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseStatusCodeSame(200);
    }
}
