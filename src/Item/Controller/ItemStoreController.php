<?php

declare(strict_types=1);

namespace App\Item\Controller;

use App\Item\DTO\ItemData;
use App\Item\Enum\ItemType;
use App\Item\Message\CreateItem;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/items', name: 'api.v1.item.store', methods: ['POST'])]
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

        $item = $this->commandBus->dispatch($message);

        return $this->json(
            ItemData::fromEntity($item)->toArray(),
            Response::HTTP_CREATED,
        );
    }
}
