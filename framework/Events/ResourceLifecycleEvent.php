<?php

declare(strict_types=1);

namespace Framework\Events;

use Admin\Contracts\Resource\BaseResource;

class ResourceLifecycleEvent extends Event
{
    private string $hook;
    private BaseResource $resource;
    private array $context;
    private mixed $returnValue = null;

    public function __construct(string $hook, BaseResource $resource, array $context = [])
    {
        parent::__construct('resource.lifecycle');
        $this->hook = $hook;
        $this->resource = $resource;
        $this->context = $context;
    }

    public function getHook(): string
    {
        return $this->hook;
    }

    public function getResource(): BaseResource
    {
        return $this->resource;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function setReturnValue(mixed $value): void
    {
        $this->returnValue = $value;
    }

    public function getReturnValue(): mixed
    {
        return $this->returnValue;
    }
}