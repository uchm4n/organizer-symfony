<?php

declare(strict_types=1);

namespace App\Workspace\Controller;

use App\Workspace\DTO\WorkspaceData;
use App\Workspace\Message\GetWorkspaceById;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/workspaces/{workspaceId}', name: 'api.v1.workspace.general.', methods: ['GET'])]
final class WorkspaceShowController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $queryBus,
    ) {}

    #[Route('', name: 'show', methods: ['GET'])]
    public function __invoke(int $workspaceId): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetWorkspaceById($workspaceId));
        $workspace = $envelope->last(HandledStamp::class)->getResult();

        return $this->json(WorkspaceData::fromEntity($workspace)->toArray());
    }
}
