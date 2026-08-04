<?php

declare(strict_types=1);

namespace App\Tests\Integration\Auth\Security;

use App\Auth\Security\ApiTokenAuthenticator;
use App\Auth\Security\ApiTokenManager;
use App\Tests\Integration\IntegrationTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class ApiTokenAuthenticatorIntegrationTest extends IntegrationTestCase
{
    private ApiTokenAuthenticator $authenticator;
    private ApiTokenManager $tokenManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authenticator = self::getContainer()->get(ApiTokenAuthenticator::class);
        $this->tokenManager = self::getContainer()->get(ApiTokenManager::class);
    }

    public function testSupportsWithValidBearerHeader(): void
    {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer some-token',
        ]);

        $this->assertTrue($this->authenticator->supports($request));
    }

    public function testSupportsWithoutAuthorizationHeader(): void
    {
        $request = Request::create('/', 'GET');

        $this->assertFalse($this->authenticator->supports($request));
    }

    public function testSupportsWithBasicAuthHeader(): void
    {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Basic dXNlcjpwYXNz',
        ]);

        $this->assertFalse($this->authenticator->supports($request));
    }

    public function testAuthenticateWithValidToken(): void
    {
        $user = $this->createUser();
        $token = $this->tokenManager->createToken($user, 'auth-test');

        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $token->getPlainTextToken(),
        ]);

        $passport = $this->authenticator->authenticate($request);

        $this->assertInstanceOf(\Symfony\Component\Security\Http\Authenticator\Passport\Passport::class, $passport);
    }

    public function testAuthenticateWithInvalidToken(): void
    {
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer invalid-token-12345',
        ]);

        $this->expectException(AuthenticationException::class);
        $this->authenticator->authenticate($request);
    }

    public function testOnAuthenticationFailureReturnsProblemJson(): void
    {
        $request = Request::create('/api/v1/test', 'GET');
        $exception = new AuthenticationException('Test failure');

        $response = $this->authenticator->onAuthenticationFailure($request, $exception);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertStringContainsString('application/problem+json', $response->headers->get('content-type'));
    }
}
