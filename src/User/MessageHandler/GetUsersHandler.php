<?php

declare(strict_types=1);

namespace App\User\MessageHandler;

use App\Shared\DTO\PaginatedCollection;
use App\User\Entity\User;
use App\User\Message\GetUsers;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(bus: 'query.bus')]
final class GetUsersHandler
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {}

    public function __invoke(GetUsers $message): PaginatedCollection
    {
        $qb = $this->em->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->orderBy('u.id', 'ASC')
            ->setMaxResults($message->perPage)
            ->setFirstResult(($message->page - 1) * $message->perPage);

        $paginator = new Paginator($qb->getQuery());

        $totalQuery = $this->em->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from(User::class, 'u');

        $total = (int) $totalQuery->getQuery()->getSingleScalarResult();

        return new PaginatedCollection(
            items: iterator_to_array($paginator),
            total: $total,
            page: $message->page,
            perPage: $message->perPage,
        );
    }
}
