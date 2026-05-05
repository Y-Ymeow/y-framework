<?php

declare(strict_types=1);

namespace Framework\Exception;

class HttpClientException extends \RuntimeException
{
    private int $status;

    public function __construct(int $status, string $body = '', ?\Throwable $previous = null)
    {
        $this->status = $status;
        parent::__construct("HTTP request failed with status {$status}: {$body}", $status, $previous);
    }

    public function getStatus(): int
    {
        return $this->status;
    }
}
