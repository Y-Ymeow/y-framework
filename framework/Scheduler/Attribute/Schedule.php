<?php

declare(strict_types=1);

namespace Framework\Scheduler\Attribute;

#[\Attribute(\Attribute::TARGET_METHOD)]
class Schedule
{
    public function __construct(
        public string $expression, // Cron 表达式，例如 "* * * * *"
        public array $params = [],
    ) {}
}
