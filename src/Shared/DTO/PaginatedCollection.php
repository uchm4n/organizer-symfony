<?php

declare(strict_types=1);

namespace App\Shared\DTO;

final readonly class PaginatedCollection
{
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {}

    public function toArray(): array
    {
        return [
            'data'  => $this->items,
            'meta'  => [
                'total'    => $this->total,
                'page'     => $this->page,
                'per_page' => $this->perPage,
            ],
        ];
    }
}
