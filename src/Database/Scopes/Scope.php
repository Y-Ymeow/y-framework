<?php

declare(strict_types=1);

namespace Framework\Database\Scopes;

use Framework\Database\Contracts\QueryBuilderInterface;
use Framework\Database\Model;

interface Scope
{
    public function apply(QueryBuilderInterface $query, Model $model): void;
}