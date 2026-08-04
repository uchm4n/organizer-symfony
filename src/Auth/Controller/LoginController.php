<?php

declare(strict_types=1);

namespace App\Auth\Controller;

use App\Auth\Message\LoginUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/login', name: 'api.v1.auth.login', methods: ['POST'])]
final class LoginController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $email = $request->request->get('email');
        $password = $request->request->get('password');

        if ($email === null || $password === null) {
            throw new HttpException(Response::HTTP_BAD_REQUEST, 'Missing email or password.');
        }

        $message = new LoginUser(
            email: $email,
            password: $password,
        );

        $envelope = $this->commandBus->dispatch($message);
        /** @var \App\User\Entity\ApiToken $token */
        $token = $envelope->last(HandledStamp::class)->getResult();

        return $this->json([
            'access_token' => $token->getPlainTextToken(),
            'token_type'   => 'Bearer',
        ]);
    }
}
