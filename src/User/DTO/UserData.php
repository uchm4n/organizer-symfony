<?php

declare(strict_types=1);

namespace App\User\DTO;

use App\User\Entity\User;

final readonly class UserData
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
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
