<?php

declare(strict_types=1);

namespace App\Workspace\DTO;

use App\Workspace\Entity\Workspace;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'WorkspaceData',
    description: 'Workspace resource',
    type: 'object',
)]
final readonly class WorkspaceData
{
    public function __construct(
        #[OA\Property(property: 'id', type: 'integer', example: 1)]
        public int $id,
        #[OA\Property(property: 'name', type: 'string', example: 'Personal')]
        public string $name,
        #[OA\Property(property: 'settings', type: 'object', nullable: true, description: 'Workspace settings')]
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
