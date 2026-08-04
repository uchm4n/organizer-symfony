<?php

declare(strict_types=1);

namespace App\Shared\Exception;

use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

final class ResourceNotFoundException extends UnrecoverableMessageHandlingException
{
    public static function forResource(string $resource): self
    {
        return new self(sprintf('%s not found.', $resource));
    }
}
