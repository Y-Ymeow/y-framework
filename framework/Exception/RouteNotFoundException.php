<?php

declare(strict_types=1);

namespace Framework\Exception;

class RouteNotFoundException extends \RuntimeException
{
    public function __construct(string $name = '', ?\Throwable $previous = null)
    {
        $message = $name ? "Route [{$name}] not found" : 'Route not found';
        parent::__construct($message, 0, $previous);
    }
}
