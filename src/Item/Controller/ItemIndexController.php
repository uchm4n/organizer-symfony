<?php

declare(strict_types=1);

namespace App\Item\Controller;

use App\Item\DTO\ItemData;
use App\Item\Message\GetItems;
use App\Shared\DTO\ProblemResponse;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/items', name: 'api.v1.item.index', methods: ['GET'])]
#[OA\Tag(name: 'Item')]
#[OA\Response(
    response: 200,
    description: 'List of current user\'s items',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: new Model(type: ItemData::class))),
        ],
        type: 'object',
    ),
)]
#[OA\Response(response: 401, description: 'Unauthorized.', content: new OA\JsonContent(ref: new Model(type: ProblemResponse::class)))]
final class ItemIndexController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $queryBus,
    ) {}

    public function __invoke(): JsonResponse
    {
        /** @var \App\User\Entity\User $user */
        $user = $this->getUser();

        $workspace = $user->getWorkspace();
        if ($workspace === null) {
            return $this->json(['data' => []]);
        }

        $envelope = $this->queryBus->dispatch(new GetItems($workspace->getId()));
        $items = $envelope->last(HandledStamp::class)->getResult();

        return $this->json([
            'data' => array_map(
                fn ($item) => ItemData::fromEntity($item)->toArray(),
                $items
            ),
        ]);
    }
}
