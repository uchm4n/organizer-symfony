<?php

declare(strict_types=1);

namespace App\Workspace\DTO;

use App\Workspace\Entity\Workspace;

final readonly class WorkspaceData
{
    public function __construct(
        public int $id,
        public string $name,
        public ?array $settings,
    ) {}

    public static function fromEntity(Workspace $workspace): self
    {
        return new self(
            id: $workspace->getId(),
            name: $workspace->getName(),
            settings: $workspace->getSettings(),
        );
    }

    public function toArray(): array
    {
        return [
            'id'       => $this->id,
            'name'     => $this->name,
            'settings' => $this->settings,
        ];
    }
}
