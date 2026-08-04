<?php

declare(strict_types=1);

namespace App\Tests\Auth\Controller;

use App\Tests\Functional\FunctionalApiTestCase;

class LoginControllerTest extends FunctionalApiTestCase
{
    public function testLoginEndpointReturnsJson(): void
    {
        $this->client->request('POST', '/api/v1/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $this->assertResponseHeaderSame('content-type', 'application/problem+json');
        $this->assertResponseStatusCodeSame(401);
    }
}
