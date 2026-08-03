<?php

declare(strict_types=1);

namespace App\User\Enum;

enum Role: string
{
    case Admin = 'admin';
    case User  = 'user';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::User  => 'User',
        };
    }

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }

    public function isUser(): bool
    {
        return $this === self::User;
    }
}
