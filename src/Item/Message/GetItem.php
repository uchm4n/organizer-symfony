<?php

declare(strict_types=1);

namespace App\Item\Message;

final readonly class GetItem
{
    public function __construct(
        public int $itemId,
    ) {}
}
