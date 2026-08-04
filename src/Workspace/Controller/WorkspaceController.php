<?php

declare(strict_types=1);

namespace App\Workspace\Controller;

use App\Shared\DTO\ProblemResponse;
use App\Workspace\DTO\WorkspaceData;
use App\Workspace\Message\CreateWorkspace;
use App\Workspace\Message\GetWorkspace;
use App\Workspace\Message\UpdateWorkspace;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/workspace', name: 'api.v1.workspace.')]
final class WorkspaceController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus,
        private MessageBusInterface $queryBus,
    ) {}

    #[Route('', name: 'show', methods: ['GET'])]
    #[OA\Tag(name: 'Workspace')]
    #[OA\Response(response: 200, description: 'Current user\'s workspace', content: new OA\JsonContent(ref: new Model(type: WorkspaceData::class)))]
    #[OA\Response(response: 401, description: 'Unauthorized.', content: new OA\JsonContent(ref: new Model(type: ProblemResponse::class)))]
    #[OA\Response(response: 404, description: 'Workspace not found.', content: new OA\JsonContent(ref: new Model(type: ProblemResponse::class)))]
    public function show(): JsonResponse
    {
        /** @var \App\User\Entity\User $user */
        $user = $this->getUser();

        $envelope = $this->queryBus->dispatch(new GetWorkspace($user->getId()));
        $workspace = $envelope->last(HandledStamp::class)->getResult();

        return $this->json(WorkspaceData::fromEntity($workspace)->toArray());
    }

    #[Route('', name: 'store', methods: ['POST'])]
    #[OA\Tag(name: 'Workspace')]
    #[OA\Response(response: 201, description: 'Workspace created', content: new OA\JsonContent(ref: new Model(type: WorkspaceData::class)))]
    #[OA\Response(response: 400, description: 'Invalid request.', content: new OA\JsonContent(ref: new Model(type: ProblemResponse::class)))]
    #[OA\Response(response: 401, description: 'Unauthorized.', content: new OA\JsonContent(ref: new Model(type: ProblemResponse::class)))]
    #[OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: new Model(type: ProblemResponse::class)))]
    #[OA\RequestBody(
        description: 'Workspace to create',
        required: true,
        content: new OA\JsonContent(
            required: ['name'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Personal'),
                new OA\Property(property: 'settings', type: 'object', nullable: true, description: 'Workspace settings'),
            ],
            type: 'object',
        ),
    )]
    public function store(Request $request): JsonResponse
    {
        /** @var \App\User\Entity\User $user */
        $user = $this->getUser();

        $envelope = $this->commandBus->dispatch(new CreateWorkspace(
            userId: $user->getId(),
            name: $request->request->get('name'),
            settings: $request->request->all('settings'),
        ));
        $workspace = $envelope->last(HandledStamp::class)->getResult();

        return $this->json(
            WorkspaceData::fromEntity($workspace)->toArray(),
            Response::HTTP_CREATED,
        );
    }

    #[Route('', name: 'update', methods: ['PATCH'])]
    #[OA\Tag(name: 'Workspace')]
    #[OA\Response(response: 200, description: 'Workspace updated', content: new OA\JsonContent(ref: new Model(type: WorkspaceData::class)))]
    #[OA\Response(response: 400, description: 'Invalid request.', content: new OA\JsonContent(ref: new Model(type: ProblemResponse::class)))]
    #[OA\Response(response: 401, description: 'Unauthorized.', content: new OA\JsonContent(ref: new Model(type: ProblemResponse::class)))]
    #[OA\Response(response: 404, description: 'Workspace not found.', content: new OA\JsonContent(ref: new Model(type: ProblemResponse::class)))]
    #[OA\Response(response: 422, description: 'Validation failed.', content: new OA\JsonContent(ref: new Model(type: ProblemResponse::class)))]
    #[OA\RequestBody(
        description: 'Fields to update',
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Personal'),
                new OA\Property(property: 'settings', type: 'object', nullable: true, description: 'Workspace settings'),
            ],
            type: 'object',
        ),
    )]
    public function update(Request $request): JsonResponse
    {
        /** @var \App\User\Entity\User $user */
        $user = $this->getUser();

        $envelope = $this->queryBus->dispatch(new GetWorkspace($user->getId()));
        $workspace = $envelope->last(HandledStamp::class)->getResult();

        $envelope = $this->commandBus->dispatch(new UpdateWorkspace(
            workspaceId: $workspace->getId(),
            name: $request->request->get('name'),
            settings: $request->request->all('settings'),
        ));
        $workspace = $envelope->last(HandledStamp::class)->getResult();

        return $this->json(WorkspaceData::fromEntity($workspace)->toArray());
    }
}
