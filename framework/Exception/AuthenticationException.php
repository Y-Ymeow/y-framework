<?php

declare(strict_types=1);

namespace Framework\Exception;

class AuthenticationException extends \RuntimeException
{
    public function __construct(string $message = 'Unauthenticated.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 401, $previous);
    }
}
