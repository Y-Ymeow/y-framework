<?php

declare(strict_types=1);

namespace Framework\Database\Model;

class MassAssignmentException extends \RuntimeException
{
    public function __construct(string $key)
    {
        parent::__construct("Mass assignment detected for key [{$key}] which is not fillable.");
    }
}