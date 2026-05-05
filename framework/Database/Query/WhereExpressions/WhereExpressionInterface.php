<?php

declare(strict_types=1);

namespace Framework\Database\Query\WhereExpressions;

interface WhereExpressionInterface
{
    public function getBoolean(): string;

    public function getType(): string;
}
