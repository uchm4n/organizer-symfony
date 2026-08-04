<?php

declare(strict_types=1);

namespace App\User\Controller;

use App\Shared\DTO\ProblemResponse;
use App\User\DTO\UserCollectionData;
use App\User\DTO\UserData;
use App\User\Message\GetUsers;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/users', name: 'api.v1.user.index', methods: ['GET'])]
#[OA\Tag(name: 'User')]
#[OA\Response(
    response: 200,
    description: 'Paginated list of users',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: new Model(type: UserData::class))),
            new OA\Property(
                property: 'meta',
                type: 'object',
                properties: [
                    new OA\Property(property: 'total', type: 'integer', example: 42),
                    new OA\Property(property: 'page', type: 'integer', example: 1),
                    new OA\Property(property: 'per_page', type: 'integer', example: 15),
                ],
            ),
        ],
        type: 'object',
    ),
)]
#[OA\Response(response: 401, description: 'Unauthorized.', content: new OA\JsonContent(ref: new Model(type: ProblemResponse::class)))]
#[OA\Response(response: 403, description: 'Forbidden. Admin role required.', content: new OA\JsonContent(ref: new Model(type: ProblemResponse::class)))]
final class UsersController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $queryBus,
    ) {}

    public function __invoke(): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetUsers());
        $collection = $envelope->last(HandledStamp::class)->getResult();

        return $this->json(UserCollectionData::fromPaginatedCollection($collection)->toArray());
    }
}
