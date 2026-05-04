<?php

declare(strict_types=1);

namespace Framework\Events\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class HookListener extends Listen
{
}
