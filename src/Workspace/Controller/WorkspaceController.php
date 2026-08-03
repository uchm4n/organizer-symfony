<?php

declare(strict_types=1);

namespace App\Workspace\Controller;

use App\Workspace\DTO\WorkspaceData;
use App\Workspace\Message\CreateWorkspace;
use App\Workspace\Message\GetWorkspace;
use App\Workspace\Message\UpdateWorkspace;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/workspace', name: 'api.v1.workspace.', methods: ['GET|POST|PATCH'])]
final class WorkspaceController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus,
        private MessageBusInterface $queryBus,
    ) {}

    #[Route('', name: 'show', methods: ['GET'])]
    public function show(): JsonResponse
    {
        /** @var \App\User\Entity\User $user */
        $user = $this->getUser();

        $workspace = $this->queryBus->dispatch(new GetWorkspace($user->getId()));

        return $this->json(WorkspaceData::fromEntity($workspace)->toArray());
    }

    #[Route('', name: 'store', methods: ['POST'])]
    public function store(Request $request): JsonResponse
    {
        /** @var \App\User\Entity\User $user */
        $user = $this->getUser();

        $workspace = $this->commandBus->dispatch(new CreateWorkspace(
            userId: $user->getId(),
            name: $request->request->get('name'),
            settings: $request->request->all('settings'),
        ));

        return $this->json(
            WorkspaceData::fromEntity($workspace)->toArray(),
            Response::HTTP_CREATED,
        );
    }

    #[Route('', name: 'update', methods: ['PATCH'])]
    public function update(Request $request): JsonResponse
    {
        /** @var \App\User\Entity\User $user */
        $user = $this->getUser();

        $workspace = $this->queryBus->dispatch(new GetWorkspace($user->getId()));

        $workspace = $this->commandBus->dispatch(new UpdateWorkspace(
            workspaceId: $workspace->getId(),
            name: $request->request->get('name'),
            settings: $request->request->all('settings'),
        ));

        return $this->json(WorkspaceData::fromEntity($workspace)->toArray());
    }
}
