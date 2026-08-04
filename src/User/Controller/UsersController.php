<?php

declare(strict_types=1);

namespace App\User\Controller;

use App\User\DTO\UserCollectionData;
use App\User\Message\GetUsers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/users', name: 'api.v1.user.index', methods: ['GET'])]
final class UsersController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $queryBus,
    ) {}

    public function __invoke(): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetUsers());
        $collection = $envelope->last(HandledStamp::class)->getResult();

        return $this->json(UserCollectionData::fromPaginatedCollection($collection)->toArray());
    }
}
