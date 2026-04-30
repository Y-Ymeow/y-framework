<?php

declare(strict_types=1);

namespace Framework\Http;

use Dom\Element;
use Framework\Component\Live\LiveComponent;
use Framework\UX\UXComponent;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class Response
{
    private SymfonyResponse $sfResponse;

    public function __construct(string|SymfonyResponse $content = '', int $status = 200, array $headers = [])
    {
        if ($content instanceof SymfonyResponse) {
            $this->sfResponse = $content;
        } else {
            $this->sfResponse = new SymfonyResponse($content, $status, $headers);
        }
    }

    public static function fromSymfony(SymfonyResponse $response): self
    {
        return new self($response);
    }

    public static function json(mixed $data, int $status = 200, array $headers = []): self
    {
        $response = new self('', $status, $headers);
        $response->sfResponse->headers->set('Content-Type', 'application/json');
        $response->sfResponse->setContent(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response;
    }

    public static function html(mixed $html, int $status = 200, array $headers = []): self
    {
        $doc = \Framework\View\Document\Document::make();
        $html = $doc->main($html)->render();
        
        $response = new self($html, $status, $headers);
        $response->sfResponse->headers->set('Content-Type', 'text/html; charset=utf-8');
        return $response;
    }

    public static function redirect(string $url, int $status = 302): self
    {
        $response = new self('', $status);
        $response->sfResponse->headers->set('Location', $url);
        return $response;
    }

    public function send(): void
    {
        $this->sfResponse->send();
    }

    public function getSfResponse(): SymfonyResponse
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

    public function getStatus(): int
    {
        return $this->sfResponse->getStatusCode();
    }

    public function getStatusCode(): int
    {
        return $this->sfResponse->getStatusCode();
    }

    public function getContent(): string
    {
        return $this->sfResponse->getContent();
    }
}
