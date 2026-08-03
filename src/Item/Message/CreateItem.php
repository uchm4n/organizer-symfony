<?php

declare(strict_types=1);

namespace App\Item\Message;

use App\Item\Enum\ItemType;

final readonly class CreateItem
{
    public function __construct(
        public int $workspaceId,
        public ItemType $type,
        public string $title,
        public ?int $parentId = null,
        public ?array $data = null,
        public int $sortOrder = 0,
    ) {}
}
