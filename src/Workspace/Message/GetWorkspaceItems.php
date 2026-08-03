<?php

declare(strict_types=1);

namespace App\Workspace\Message;

final readonly class GetWorkspaceItems
{
    public function __construct(
        public int $workspaceId,
    ) {}
}
