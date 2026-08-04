<?php

declare(strict_types=1);

namespace App\Tests\Functional\Workspace;

use App\Tests\Functional\FunctionalApiTestCase;

class WorkspaceApiTest extends FunctionalApiTestCase
{
    private const EMAIL = 'ws@example.com';
    private const PASSWORD = 'pass123';
    private const WORKSPACE_NAME = 'Test Workspace';

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

    public function testCreateWorkspace(): void
    {
        $token = $this->loginAndGetToken();
        $this->client->request('POST', '/api/v1/workspace', [
            'name' => self::WORKSPACE_NAME,
        ], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(201);

        $data = $this->getJsonResponse();
        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('name', $data);
        $this->assertSame(self::WORKSPACE_NAME, $data['name']);
    }

    public function testGetWorkspace(): void
    {
        $token = $this->loginAndGetToken();

        $this->client->request('POST', '/api/v1/workspace', [
            'name' => self::WORKSPACE_NAME,
        ], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->client->request('GET', '/api/v1/workspace', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse();
        $this->assertSame(self::WORKSPACE_NAME, $data['name']);
    }

    public function testShowWorkspaceById(): void
    {
        $token = $this->loginAndGetToken();

        $this->client->request('POST', '/api/v1/workspace', [
            'name' => self::WORKSPACE_NAME,
        ], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);
        $workspace = $this->getJsonResponse();

        $this->client->request('GET', '/api/v1/workspaces/' . $workspace['id'], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse();
        $this->assertSame($workspace['id'], $data['id']);
        $this->assertSame(self::WORKSPACE_NAME, $data['name']);
    }

    public function testCreateWorkspaceRequiresAuth(): void
    {
        $this->client->request('POST', '/api/v1/workspace', [
            'name' => self::WORKSPACE_NAME,
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testCreateWorkspaceWithSettings(): void
    {
        $token = $this->loginAndGetToken();
        $this->client->request('POST', '/api/v1/workspace', [
            'name' => self::WORKSPACE_NAME,
            'settings' => [
                'theme' => 'dark',
                'language' => 'en',
            ],
        ], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(201);

        $data = $this->getJsonResponse();
        $this->assertArrayHasKey('settings', $data);
        $this->assertSame('dark', $data['settings']['theme']);
        $this->assertSame('en', $data['settings']['language']);
    }
}
