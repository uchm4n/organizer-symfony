<?php

declare(strict_types=1);

namespace App\Item\Message;

final readonly class GetItems
{
    public function __construct(
        public int $workspaceId,
        public ?int $parentId = null,
    ) {}
}
