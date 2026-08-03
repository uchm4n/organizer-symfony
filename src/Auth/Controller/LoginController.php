<?php

declare(strict_types=1);

namespace App\Auth\Controller;

use App\Auth\Message\LoginUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/login', name: 'api.v1.auth.login', methods: ['POST'])]
final class LoginController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $message = new LoginUser(
            email: $request->request->get('email'),
            password: $request->request->get('password'),
        );

        $token = $this->commandBus->dispatch($message);

        return $this->json([
            'access_token' => $token->getPlainTextToken(),
            'token_type'   => 'Bearer',
        ]);
    }
}
