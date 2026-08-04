<?php

declare(strict_types=1);

namespace App\Workspace\Controller;

use App\Shared\DTO\ProblemResponse;
use App\Workspace\DTO\WorkspaceData;
use App\Workspace\Message\GetWorkspaceById;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/workspaces/{workspaceId}', name: 'api.v1.workspace.general.', methods: ['GET'])]
#[OA\Tag(name: 'Workspace')]
#[OA\Parameter(name: 'workspaceId', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))]
#[OA\Response(response: 200, description: 'Workspace', content: new OA\JsonContent(ref: new Model(type: WorkspaceData::class)))]
#[OA\Response(response: 401, description: 'Unauthorized.', content: new OA\JsonContent(ref: new Model(type: ProblemResponse::class)))]
#[OA\Response(response: 404, description: 'Workspace not found.', content: new OA\JsonContent(ref: new Model(type: ProblemResponse::class)))]
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
