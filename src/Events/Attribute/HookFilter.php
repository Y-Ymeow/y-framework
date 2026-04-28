<?php

declare(strict_types=1);

namespace Framework\Events\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class HookFilter
{
    public string $hook;
    public int $priority;
    public int $acceptedArgs;

    public function __construct(string $hook, int $priority = 10, int $acceptedArgs = 1)
    {
        $this->hook = $hook;
        $this->priority = $priority;
        $this->acceptedArgs = $acceptedArgs;
    }
}
