<?php

declare(strict_types=1);

namespace App\Tests\Functional\Item;

use App\Tests\Functional\FunctionalApiTestCase;

class ItemApiTest extends FunctionalApiTestCase
{
    private const EMAIL = 'item@example.com';
    private const PASSWORD = 'pass123';
    private const NOTE_TITLE = 'Test Note';
    private const TODO_TITLE = 'Test Todo';

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

    private function createWorkspace(string $token): array
    {
        $this->client->request('POST', '/api/v1/workspace', [
            'name' => 'Test Workspace',
        ], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        return json_decode($this->client->getResponse()->getContent(), true);
    }

    private function createItem(string $token, int $type, string $title, int $workspaceId): array
    {
        $this->client->request('POST', '/api/v1/items', [
            'workspace_id' => $workspaceId,
            'type' => $type,
            'title' => $title,
        ], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        return json_decode($this->client->getResponse()->getContent(), true);
    }

    private function getJsonResponse(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true);
    }

    public function testCreateItem(): void
    {
        $token = $this->loginAndGetToken();
        $workspace = $this->createWorkspace($token);

        $this->createItem($token, 1, self::NOTE_TITLE, $workspace['id']);

        $this->assertResponseStatusCodeSame(201);

        $data = $this->getJsonResponse();
        $this->assertArrayHasKey('id', $data);
        $this->assertSame(self::NOTE_TITLE, $data['title']);
    }

    public function testListItems(): void
    {
        $token = $this->loginAndGetToken();
        $workspace = $this->createWorkspace($token);

        $this->createItem($token, 1, self::NOTE_TITLE, $workspace['id']);
        $this->createItem($token, 2, self::TODO_TITLE, $workspace['id']);

        $this->client->request('GET', '/api/v1/items', [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse();
        $this->assertCount(2, $data['data']);
    }

    public function testGetItemById(): void
    {
        $token = $this->loginAndGetToken();
        $workspace = $this->createWorkspace($token);

        $created = $this->createItem($token, 1, self::NOTE_TITLE, $workspace['id']);

        $this->client->request('GET', '/api/v1/items/' . $created['id'], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse();
        $this->assertSame(self::NOTE_TITLE, $data['title']);
    }

    public function testUpdateItem(): void
    {
        $token = $this->loginAndGetToken();
        $workspace = $this->createWorkspace($token);

        $created = $this->createItem($token, 1, self::NOTE_TITLE, $workspace['id']);

        $this->client->request('PATCH', '/api/v1/items/' . $created['id'], [
            'title' => 'Updated Title',
        ], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();

        $data = $this->getJsonResponse();
        $this->assertSame('Updated Title', $data['title']);
    }

    public function testDeleteItem(): void
    {
        $token = $this->loginAndGetToken();
        $workspace = $this->createWorkspace($token);

        $created = $this->createItem($token, 1, self::NOTE_TITLE, $workspace['id']);

        $this->client->request('DELETE', '/api/v1/items/' . $created['id'], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseIsSuccessful();

        $this->client->request('GET', '/api/v1/items/' . $created['id'], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token,
        ]);

        $this->assertResponseStatusCodeSame(404);
    }
}
