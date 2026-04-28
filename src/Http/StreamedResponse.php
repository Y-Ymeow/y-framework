<?php

declare(strict_types=1);

namespace Framework\Http;

use Symfony\Component\HttpFoundation\StreamedResponse as SymfonyStreamedResponse;

class StreamedResponse
{
    private SymfonyStreamedResponse $sfResponse;

    public function __construct(callable $callback, int $status = 200, array $headers = [])
    {
        $this->sfResponse = new SymfonyStreamedResponse($callback, $status, $headers);
    }

    public function send(): void
    {
        $this->sfResponse->send();
    }

    public function getSfResponse(): SymfonyStreamedResponse
    {
        return $this->sfResponse;
    }

    public function setHeader(string $key, string $value): self
    {
        $this->sfResponse->headers->set($key, $value);
        return $this;
    }

    public function setStatusCode(int $code): self
    {
        $this->sfResponse->setStatusCode($code);
        return $this;
    }
}
