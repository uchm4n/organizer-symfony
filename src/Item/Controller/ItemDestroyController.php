<?php

declare(strict_types=1);

namespace App\Item\Controller;

use App\Item\Message\DeleteItem;
use App\Shared\DTO\ProblemResponse;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/items/{itemId}', name: 'api.v1.item.destroy', methods: ['DELETE'])]
#[OA\Tag(name: 'Item')]
#[OA\Parameter(name: 'itemId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
#[OA\Response(response: 204, description: 'Item deleted')]
#[OA\Response(response: 401, description: 'Unauthorized.', content: new OA\JsonContent(ref: new Model(type: ProblemResponse::class)))]
#[OA\Response(response: 404, description: 'Item not found.', content: new OA\JsonContent(ref: new Model(type: ProblemResponse::class)))]
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
