<?php

declare(strict_types=1);

namespace Framework\Events;

use Framework\Http\Request\Request;

class RequestEvent extends Event
{
    private Request $request;

    public function __construct(Request $request)
    {
        parent::__construct('request.received');
        $this->request = $request;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}
