<?php

declare(strict_types=1);

namespace App\Auth\Security;

use App\Shared\HttpKernel\ProblemRenderer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;

final class ApiTokenAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private ApiTokenManager $tokenManager,
    ) {}

    public function supports(Request $request): bool
    {
        return $request->headers->has('Authorization')
            && str_starts_with($request->headers->get('Authorization'), 'Bearer ');
    }

    public function authenticate(Request $request): Passport
    {
        $header = $request->headers->get('Authorization');
        $plainToken = substr($header, 7);

        $hashedToken = hash('sha256', $plainToken);
        $token = $this->tokenManager->findValidToken($hashedToken);

        if ($token === null) {
            throw new AuthenticationException('Invalid or expired token.');
        }

        return new SelfValidatingPassport(
            new UserBadge($token->getUser()->getUserIdentifier())
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        return ProblemRenderer::response(
            401,
            'Unauthorized',
            $exception->getMessage()
        );
    }
}
