<?php

declare(strict_types=1);

namespace Framework\Exception;

class AuthorizationException extends \RuntimeException
{
    public function __construct(string $message = 'This action is unauthorized.', ?\Throwable $previous = null)
    {
        parent::__construct($message, 403, $previous);
    }
}
