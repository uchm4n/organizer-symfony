<?php

declare(strict_types=1);

namespace App\Workspace\Controller;

use App\Item\DTO\ItemData;
use App\Shared\DTO\ProblemResponse;
use App\Workspace\Message\GetWorkspaceItems;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/workspaces/{workspaceId}/items', name: 'api.v1.workspace.items.', methods: ['GET'])]
#[OA\Tag(name: 'Workspace')]
#[OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
#[OA\Response(
    response: 200,
    description: 'List of workspace items',
    content: new OA\JsonContent(
        properties: [
            new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: new Model(type: ItemData::class))),
        ],
        type: 'object',
    ),
)]
#[OA\Response(response: 401, description: 'Unauthorized.', content: new OA\JsonContent(ref: new Model(type: ProblemResponse::class)))]
#[OA\Response(response: 404, description: 'Workspace not found.', content: new OA\JsonContent(ref: new Model(type: ProblemResponse::class)))]
final class WorkspaceIndexController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $queryBus,
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function __invoke(int $workspaceId): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetWorkspaceItems($workspaceId));
        $items = $envelope->last(HandledStamp::class)->getResult();

        return $this->json([
            'data' => array_map(
                fn ($item) => ItemData::fromEntity($item)->toArray(),
                $items
            ),
        ]);
    }
}
