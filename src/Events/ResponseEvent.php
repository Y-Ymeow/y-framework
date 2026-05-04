<?php

declare(strict_types=1);

namespace Framework\Events;

use Framework\Http\Request\Request;
use Framework\Http\Response\Response;

class ResponseEvent extends Event
{
    private Response $response;
    private Request $request;

    public function __construct(string $name, Response $response, Request $request)
    {
        parent::__construct($name);
        $this->response = $response;
        $this->request = $request;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function setResponse(Response $response): static
    {
        $this->response = $response;
        return $this;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}
