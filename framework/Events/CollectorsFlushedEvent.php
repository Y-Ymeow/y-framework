<?php

declare(strict_types=1);

namespace Framework\Events;

class CollectorsFlushedEvent extends Event
{
    private string $type;
    private array $items;

    public function __construct(string $type, array $items)
    {
        parent::__construct('collectors.flushed');
        $this->type = $type;
        $this->items = $items;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getItems(): array
    {
        return $this->items;
    }
}