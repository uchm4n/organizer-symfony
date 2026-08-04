<?php

declare(strict_types=1);

namespace App\Item\Controller;

use App\Item\DTO\ItemData;
use App\Item\Enum\ItemType;
use App\Item\Message\UpdateItem;
use App\Shared\DTO\ProblemResponse;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/items/{itemId}', name: 'api.v1.item.update', methods: ['PATCH'])]
#[OA\Tag(name: 'Item')]
#[OA\Parameter(name: 'itemId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
#[OA\Response(response: 200, description: 'Item updated', content: new OA\JsonContent(ref: new Model(type: ItemData::class)))]
#[OA\Response(response: 400, description: 'Invalid request.', content: new OA\JsonContent(ref: new Model(type: ProblemResponse::class)))]
#[OA\Response(response: 401, description: 'Unauthorized.', content: new OA\JsonContent(ref: new Model(type: ProblemResponse::class)))]
#[OA\Response(response: 404, description: 'Item not found.', content: new OA\JsonContent(ref: new Model(type: ProblemResponse::class)))]
#[OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: new Model(type: ProblemResponse::class)))]
#[OA\RequestBody(
    description: 'Fields to update',
    required: true,
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'title', type: 'string', example: 'Weekly groceries'),
            new OA\Property(property: 'type', type: 'integer', example: 1, description: 'Item type: 1=Note, 2=Todo, 3=Spreadsheet, 4=TaxFiling, 5=Event, 6=Document, 99=Custom'),
            new OA\Property(property: 'data', type: 'object', nullable: true, description: 'Type-specific payload'),
            new OA\Property(property: 'sort_order', type: 'integer', example: 0),
        ],
        type: 'object',
    ),
)]
final class ItemUpdateController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {}

    public function __invoke(int $itemId, Request $request): JsonResponse
    {
        $message = new UpdateItem(
            itemId: $itemId,
            title: $request->request->get('title'),
            type: $request->request->get('type') ? ItemType::from((int) $request->request->get('type')) : null,
            data: $request->request->all('data'),
            sortOrder: $request->request->has('sort_order') ? (int) $request->request->get('sort_order') : null,
        );

        $envelope = $this->commandBus->dispatch($message);
        $item = $envelope->last(HandledStamp::class)->getResult();

        return $this->json(ItemData::fromEntity($item)->toArray());
    }
}
