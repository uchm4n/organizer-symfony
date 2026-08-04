<?php

declare(strict_types=1);

namespace App\Item\DTO;

use App\Item\Entity\Item;

final readonly class ItemData
{
    public function __construct(
        public int $id,
        public int $workspaceId,
        public ?int $parentId,
        public string $type,
        public string $title,
        public ?array $data,
        public int $sortOrder,
    ) {}

    public static function fromEntity(Item $item): self
    {
        return new self(
            id: $item->getId(),
            workspaceId: $item->getWorkspace()->getId(),
            parentId: $item->getParent()?->getId(),
            type: (string)$item->getType()->value,
            title: $item->getTitle(),
            data: $item->getData(),
            sortOrder: $item->getSortOrder(),
        );
    }

    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'workspace_id' => $this->workspaceId,
            'parent_id'    => $this->parentId,
            'type'         => $this->type,
            'title'        => $this->title,
            'data'         => $this->data,
            'sort_order'   => $this->sortOrder,
        ];
    }
}
