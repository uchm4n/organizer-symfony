<?php

declare(strict_types=1);

namespace App\Item\Message;

use App\Item\Enum\ItemType;

final readonly class UpdateItem
{
    public function __construct(
        public int $itemId,
        public ?string $title = null,
        public ?ItemType $type = null,
        public ?array $data = null,
        public ?int $sortOrder = null,
    ) {}
}
