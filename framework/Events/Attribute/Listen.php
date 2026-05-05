<?php

declare(strict_types=1);

namespace Framework\Events\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class Listen
{
    public function __construct(
        public string $event,
        public int $priority = 0,
        public int $acceptedArgs = 1,
    ) {}
}
