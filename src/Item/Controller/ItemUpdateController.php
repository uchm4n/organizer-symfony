<?php

declare(strict_types=1);

namespace App\Item\Controller;

use App\Item\DTO\ItemData;
use App\Item\Enum\ItemType;
use App\Item\Message\UpdateItem;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/items/{itemId}', name: 'api.v1.item.update', methods: ['PATCH'])]
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

        $item = $this->commandBus->dispatch($message);

        return $this->json(ItemData::fromEntity($item)->toArray());
    }
}
