<?php

declare(strict_types=1);

namespace App\Workspace\Controller;

use App\Item\DTO\ItemData;
use App\Workspace\Message\GetWorkspaceItems;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/workspaces/{workspaceId}/items', name: 'api.v1.workspace.items.', methods: ['GET'])]
final class WorkspaceIndexController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $queryBus,
    ) {}

    #[Route('', name: 'index', methods: ['GET'])]
    public function __invoke(int $workspaceId): JsonResponse
    {
        $items = $this->queryBus->dispatch(new GetWorkspaceItems($workspaceId));

        return $this->json([
            'data' => array_map(
                fn ($item) => ItemData::fromEntity($item)->toArray(),
                $items
            ),
        ]);
    }
}
