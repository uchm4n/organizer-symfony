<?php

declare(strict_types=1);

namespace App\User\DTO;

use App\Shared\DTO\PaginatedCollection;
use App\User\Entity\User;

final readonly class UserCollectionData
{
    public function __construct(
        public array $users,
        public int $total,
        public int $page,
        public int $perPage,
    ) {}

    public static function fromPaginatedCollection(PaginatedCollection $collection): self
    {
        return new self(
            users: array_map(
                fn (User $user) => UserData::fromEntity($user),
                $collection->items
            ),
            total: $collection->total,
            page: $collection->page,
            perPage: $collection->perPage,
        );
    }

    public function toArray(): array
    {
        return [
            'data' => array_map(
                fn (UserData $user) => $user->toArray(),
                $this->users
            ),
            'meta' => [
                'total'    => $this->total,
                'page'     => $this->page,
                'per_page' => $this->perPage,
            ],
        ];
    }
}
