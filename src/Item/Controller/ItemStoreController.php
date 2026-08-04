<?php

declare(strict_types=1);

namespace App\Item\Controller;

use App\Item\DTO\ItemData;
use App\Item\Enum\ItemType;
use App\Item\Message\CreateItem;
use App\Shared\DTO\ProblemResponse;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/items', name: 'api.v1.item.store', methods: ['POST'])]
#[OA\Tag(name: 'Item')]
#[OA\Response(response: 201, description: 'Item created', content: new OA\JsonContent(ref: new Model(type: ItemData::class)))]
#[OA\Response(response: 400, description: 'Invalid request.', content: new OA\JsonContent(ref: new Model(type: ProblemResponse::class)))]
#[OA\Response(response: 401, description: 'Unauthorized.', content: new OA\JsonContent(ref: new Model(type: ProblemResponse::class)))]
#[OA\Response(response: 404, description: 'Workspace not found.', content: new OA\JsonContent(ref: new Model(type: ProblemResponse::class)))]
#[OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: new Model(type: ProblemResponse::class)))]
#[OA\RequestBody(
    description: 'Item to create',
    required: true,
    content: new OA\JsonContent(
        required: ['workspace_id', 'type', 'title'],
        properties: [
            new OA\Property(property: 'workspace_id', type: 'integer', example: 1),
            new OA\Property(property: 'type', type: 'integer', example: 1, description: 'Item type: 1=Note, 2=Todo, 3=Spreadsheet, 4=TaxFiling, 5=Event, 6=Document, 99=Custom'),
            new OA\Property(property: 'title', type: 'string', example: 'Weekly groceries'),
            new OA\Property(property: 'parent_id', type: 'integer', nullable: true, example: null, description: 'Parent item ID'),
            new OA\Property(property: 'data', type: 'object', nullable: true, description: 'Type-specific payload'),
            new OA\Property(property: 'sort_order', type: 'integer', example: 0),
        ],
        type: 'object',
    ),
)]
final class ItemStoreController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $message = new CreateItem(
            workspaceId: (int) $request->request->get('workspace_id'),
            type: ItemType::from((int) $request->request->get('type')),
            title: $request->request->get('title'),
            parentId: $request->request->get('parent_id') ? (int) $request->request->get('parent_id') : null,
            data: $request->request->all('data'),
            sortOrder: (int) $request->request->get('sort_order', 0),
        );

        $envelope = $this->commandBus->dispatch($message);
        $item = $envelope->last(HandledStamp::class)->getResult();

        return $this->json(
            ItemData::fromEntity($item)->toArray(),
            Response::HTTP_CREATED,
        );
    }
}
