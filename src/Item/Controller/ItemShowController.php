<?php

declare(strict_types=1);

namespace App\Item\Controller;

use App\Item\DTO\ItemData;
use App\Item\Message\GetItem;
use App\Shared\DTO\ProblemResponse;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/items/{itemId}', name: 'api.v1.item.show', methods: ['GET'])]
#[OA\Tag(name: 'Item')]
#[OA\Parameter(name: 'itemId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
#[OA\Response(response: 200, description: 'Item', content: new OA\JsonContent(ref: new Model(type: ItemData::class)))]
#[OA\Response(response: 401, description: 'Unauthorized.', content: new OA\JsonContent(ref: new Model(type: ProblemResponse::class)))]
#[OA\Response(response: 404, description: 'Item not found.', content: new OA\JsonContent(ref: new Model(type: ProblemResponse::class)))]
final class ItemShowController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $queryBus,
    ) {}

    public function __invoke(int $itemId): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetItem($itemId));
        $item = $envelope->last(HandledStamp::class)->getResult();

        return $this->json(ItemData::fromEntity($item)->toArray());
    }
}
