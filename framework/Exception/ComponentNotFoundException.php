<?php

declare(strict_types=1);

namespace Framework\Exception;

class ComponentNotFoundException extends \RuntimeException
{
    public function __construct(string $class, ?\Throwable $previous = null)
    {
        parent::__construct("Invalid component: {$class}", 0, $previous);
    }
}
