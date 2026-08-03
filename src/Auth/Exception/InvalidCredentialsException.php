<?php

declare(strict_types=1);

namespace App\Auth\Exception;

use Symfony\Component\Security\Core\Exception\AuthenticationException;

final class InvalidCredentialsException extends AuthenticationException
{
    public function __construct()
    {
        parent::__construct('Invalid credentials.');
    }
}
