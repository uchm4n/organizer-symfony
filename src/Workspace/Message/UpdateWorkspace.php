<?php

declare(strict_types=1);

namespace App\Workspace\Message;

final readonly class UpdateWorkspace
{
    public function __construct(
        public int $workspaceId,
        public string $name,
        public ?array $settings = null,
    ) {}
}
