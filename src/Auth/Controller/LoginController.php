<?php

declare(strict_types=1);

namespace App\Auth\Controller;

use App\Auth\Message\LoginUser;
use App\Shared\DTO\ProblemResponse;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/login', name: 'api.v1.auth.login', methods: ['POST'])]
#[OA\Tag(name: 'Auth')]
#[OA\Response(
    response: 200,
    description: 'Login successful',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'access_token', type: 'string', example: '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef'),
            new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
        ],
        type: 'object',
    ),
)]
#[OA\Response(response: 400, description: 'Missing email or password.', content: new OA\JsonContent(ref: new Model(type: ProblemResponse::class)))]
#[OA\Response(response: 401, description: 'Invalid credentials.', content: new OA\JsonContent(ref: new Model(type: ProblemResponse::class)))]
#[OA\Response(response: 429, description: 'Too many login attempts.', content: new OA\JsonContent(ref: new Model(type: ProblemResponse::class)))]
#[OA\RequestBody(
    description: 'Login credentials',
    required: true,
    content: new OA\JsonContent(
        required: ['email', 'password'],
        properties: [
            new OA\Property(property: 'email', type: 'string', example: 'john@example.com'),
            new OA\Property(property: 'password', type: 'string', format: 'password', example: 'secret'),
        ],
        type: 'object',
    ),
)]
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
