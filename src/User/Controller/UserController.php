<?php

declare(strict_types=1);

namespace App\User\Controller;

use App\Shared\DTO\ProblemResponse;
use App\User\DTO\UserData;
use App\User\Message\GetUser;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/user', name: 'api.v1.user.show', methods: ['GET'])]
#[OA\Tag(name: 'User')]
#[OA\Response(response: 200, description: 'Authenticated user', content: new OA\JsonContent(ref: new Model(type: UserData::class)))]
#[OA\Response(response: 401, description: 'Unauthorized.', content: new OA\JsonContent(ref: new Model(type: ProblemResponse::class)))]
final class UserController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $queryBus,
    ) {}

    public function __invoke(): JsonResponse
    {
        /** @var \App\User\Entity\User $user */
        $user = $this->getUser();

        return $this->json(UserData::fromEntity($user)->toArray());
    }

    public function getQueryBus(): MessageBusInterface
    {
        return $this->queryBus;
    }
}
