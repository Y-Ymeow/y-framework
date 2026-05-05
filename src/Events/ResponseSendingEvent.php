<?php

declare(strict_types=1);

namespace Framework\Events;

use Framework\Http\Request\Request;
use Framework\Http\Response\Response;
use Framework\Http\Response\StreamedResponse;

class ResponseSendingEvent extends Event
{
    private Response|StreamedResponse $response;
    private Request $request;

    public function __construct(Response|StreamedResponse $response, Request $request)
    {
        parent::__construct('response.sending');
        $this->response = $response;
        $this->request = $request;
    }

    public function getResponse(): Response|StreamedResponse
    {
        return $this->response;
    }

    public function setResponse(Response|StreamedResponse $response): static
    {
        $this->response = $response;
        return $this;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}