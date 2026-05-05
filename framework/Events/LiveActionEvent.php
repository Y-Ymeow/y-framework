<?php

declare(strict_types=1);

namespace Framework\Events;

use Framework\Component\Live\LiveComponent;
use Framework\Http\Request\Request;

class LiveActionEvent extends Event
{
    private array $response;
    private LiveComponent $component;
    private Request $request;

    public function __construct(array $response, LiveComponent $component, Request $request)
    {
        parent::__construct('live.action.completed');
        $this->response = $response;
        $this->component = $component;
        $this->request = $request;
    }

    public function getResponse(): array
    {
        return $this->response;
    }

    public function setResponse(array $response): static
    {
        $this->response = $response;
        return $this;
    }

    public function getComponent(): LiveComponent
    {
        return $this->component;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}
