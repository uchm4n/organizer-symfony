<?php

declare(strict_types=1);

namespace App\Item\Controller;

use App\Item\DTO\ItemData;
use App\Item\Message\GetItems;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/items', name: 'api.v1.item.index', methods: ['GET'])]
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
