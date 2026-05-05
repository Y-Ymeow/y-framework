<?php

declare(strict_types=1);

namespace Framework\Exception;

class ValidationException extends \RuntimeException
{
    private array $errors;

    public function __construct(array $errors = [], string $message = 'The given data was invalid.')
    {
        $this->errors = $errors;
        parent::__construct($message, 422);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
