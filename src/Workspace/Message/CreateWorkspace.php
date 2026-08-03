<?php

declare(strict_types=1);

namespace App\Workspace\Message;

final readonly class CreateWorkspace
{
    public function __construct(
        public int $userId,
        public string $name,
        public ?array $settings = null,
    ) {}
}
