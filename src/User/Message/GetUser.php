<?php

declare(strict_types=1);

namespace App\User\Message;

final readonly class GetUser
{
    public function __construct(
        public int $userId,
    ) {}
}
