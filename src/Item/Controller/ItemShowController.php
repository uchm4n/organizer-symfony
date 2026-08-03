<?php

declare(strict_types=1);

namespace App\Item\Controller;

use App\Item\DTO\ItemData;
use App\Item\Message\GetItem;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/items/{itemId}', name: 'api.v1.item.show', methods: ['GET'])]
final class ItemShowController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $queryBus,
    ) {}

    public function __invoke(int $itemId): JsonResponse
    {
        $item = $this->queryBus->dispatch(new GetItem($itemId));

        return $this->json(ItemData::fromEntity($item)->toArray());
    }
}
