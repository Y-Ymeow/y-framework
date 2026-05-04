<?php

declare(strict_types=1);

namespace Framework\Events;

class BootEvent extends Event
{
    public function __construct(string $name)
    {
        parent::__construct($name);
    }
}
