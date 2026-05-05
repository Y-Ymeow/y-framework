<?php

declare(strict_types=1);

namespace Framework\Database\Scopes;

use Framework\Database\Contracts\QueryBuilderInterface;
use Framework\Database\Model;

class SoftDeletingScope implements Scope
{
    public function apply(QueryBuilderInterface $query, Model $model): void
    {
        $column = method_exists($model, 'getDeletedAtColumn')
            ? $model->getDeletedAtColumn()
            : 'deleted_at';

        $query->whereNull($column);
    }
}