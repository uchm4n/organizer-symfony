<?php

declare(strict_types=1);

namespace App\User\DTO;

use App\User\Entity\User;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UserData',
    description: 'User resource',
    type: 'object',
)]
final readonly class UserData
{
    public function __construct(
        #[OA\Property(property: 'id', type: 'integer', example: 1)]
        public int $id,
        #[OA\Property(property: 'name', type: 'string', example: 'John Doe')]
        public string $name,
        #[OA\Property(property: 'email', type: 'string', example: 'john@example.com')]
        public string $email,
        #[OA\Property(property: 'role', type: 'string', example: 'user', description: 'Role: user or admin')]
        public string $role,
    ) {}

    public static function fromEntity(User $user): self
    {
        return new self(
            id: $user->getId(),
            name: $user->getName(),
            email: $user->getEmail(),
            role: $user->getRole()->value,
        );
    }

    public function toArray(): array
    {
        return [
            'id'    => $this->id,
            'name'  => $this->name,
            'email' => $this->email,
            'role'  => $this->role,
        ];
    }
}
