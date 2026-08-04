<?php

declare(strict_types=1);

namespace App\Tests\Functional;

class ApiDocTest extends FunctionalApiTestCase
{
    public function testHomePageLinksToApiDoc(): void
    {
        $this->client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'text/html; charset=UTF-8');
        $this->assertStringContainsString('Organizer API', $this->client->getResponse()->getContent());
        $this->assertStringContainsString('/api/doc', $this->client->getResponse()->getContent());
    }

    public function testSwaggerUiIsPublicAndRenders(): void
    {
        $this->client->request('GET', '/api/doc');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('swagger-ui', $this->client->getResponse()->getContent());
        $this->assertStringContainsString('id="swagger-data"', $this->client->getResponse()->getContent());
        $this->assertStringContainsString('Organizer API', $this->client->getResponse()->getContent());
    }

    public function testSwaggerSpecIsPublicAndComplete(): void
    {
        $this->client->request('GET', '/api/doc.json');

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $spec = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('3.0.0', $spec['openapi']);
        $this->assertSame('Organizer API', $spec['info']['title']);
        $this->assertSame('1.0.0', $spec['info']['version']);
        $this->assertSame('Bearer', array_key_first($spec['components']['securitySchemes']));
        $this->assertSame([['Bearer' => []]], $spec['security']);

        $paths = $spec['paths'];
        foreach (['/api/v1/login', '/api/v1/user', '/api/v1/users', '/api/v1/workspace', '/api/v1/items'] as $path) {
            $this->assertArrayHasKey($path, $paths, "Path $path missing from spec");
        }

        $itemSchema = $spec['components']['schemas']['ItemData'];
        $this->assertSame(
            ['id', 'workspace_id', 'parent_id', 'type', 'title', 'data', 'sort_order'],
            array_keys($itemSchema['properties']),
        );

        $this->assertArrayHasKey('ProblemResponse', $spec['components']['schemas']);
    }

    public function testLoginOperationDocumentsRequestBody(): void
    {
        $this->client->request('GET', '/api/doc.json');

        $spec = json_decode($this->client->getResponse()->getContent(), true);
        $login = $spec['paths']['/api/v1/login']['post'];

        $this->assertSame(['Auth'], $login['tags']);
        $this->assertArrayHasKey('requestBody', $login);
        $this->assertArrayHasKey(200, $login['responses']);
        $this->assertArrayHasKey(401, $login['responses']);
    }
}
