<?php

declare(strict_types=1);

namespace App\Workspace\Message;

final readonly class GetWorkspace
{
    public function __construct(
        public int $userId,
    ) {}
}
