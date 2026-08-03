<?php

declare(strict_types=1);

namespace App\User\Message;

use App\User\Enum\Role;

final readonly class UpdateUserRole
{
    public function __construct(
        public int $userId,
        public Role $role,
    ) {}
}
