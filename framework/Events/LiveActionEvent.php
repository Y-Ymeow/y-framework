<?php

declare(strict_types=1);

namespace Framework\Events;

use Framework\Component\Live\AbstractLiveComponent;
use Framework\Http\Request\Request;

class LiveActionEvent extends Event
{
    private array $response;
    private AbstractLiveComponent $component;
    private Request $request;

    public function __construct(array $response, AbstractLiveComponent $component, Request $request)
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

    public function getComponent(): AbstractLiveComponent
    {
        return $this->component;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}
