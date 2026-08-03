<?php

declare(strict_types=1);

namespace App\Item\Controller;

use App\Item\Message\DeleteItem;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/items/{itemId}', name: 'api.v1.item.destroy', methods: ['DELETE'])]
final class ItemDestroyController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus,
    ) {}

    public function __invoke(int $itemId): Response
    {
        $this->commandBus->dispatch(new DeleteItem($itemId));

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
