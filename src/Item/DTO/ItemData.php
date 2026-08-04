<?php

declare(strict_types=1);

namespace App\Item\DTO;

use App\Item\Entity\Item;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ItemData',
    description: 'Item resource',
    type: 'object',
)]
final readonly class ItemData
{
    public function __construct(
        #[OA\Property(property: 'id', type: 'integer', example: 1)]
        public int $id,
        #[OA\Property(property: 'workspace_id', type: 'integer', example: 1)]
        public int $workspaceId,
        #[OA\Property(property: 'parent_id', type: 'integer', nullable: true, example: null)]
        public ?int $parentId,
        #[OA\Property(property: 'type', type: 'string', example: '1', description: 'Item type: 1=Note, 2=Todo, 3=Spreadsheet, 4=TaxFiling, 5=Event, 6=Document, 99=Custom')]
        public string $type,
        #[OA\Property(property: 'title', type: 'string', example: 'Weekly groceries')]
        public string $title,
        #[OA\Property(property: 'data', type: 'object', nullable: true, description: 'Type-specific payload')]
        public ?array $data,
        #[OA\Property(property: 'sort_order', type: 'integer', example: 0)]
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
