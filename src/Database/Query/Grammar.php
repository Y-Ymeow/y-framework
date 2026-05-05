<?php

declare(strict_types=1);

namespace Framework\Database\Query;

use Framework\Database\Query\WhereExpressions\BasicWhereExpression;
use Framework\Database\Query\WhereExpressions\BetweenWhereExpression;
use Framework\Database\Query\WhereExpressions\ColumnWhereExpression;
use Framework\Database\Query\WhereExpressions\DatePartWhereExpression;
use Framework\Database\Query\WhereExpressions\DateWhereExpression;
use Framework\Database\Query\WhereExpressions\ExistsWhereExpression;
use Framework\Database\Query\WhereExpressions\InWhereExpression;
use Framework\Database\Query\WhereExpressions\NestedWhereExpression;
use Framework\Database\Query\WhereExpressions\NullWhereExpression;
use Framework\Database\Query\WhereExpressions\RawWhereExpression;
use Framework\Database\Query\WhereExpressions\WhereExpressionInterface;

abstract class Grammar
{
    abstract public function compileSelect(Builder $query): array;

    abstract public function compileInsert(Builder $query, array $data): array;

    abstract public function compileUpdate(Builder $query, array $data): array;

    abstract public function compileDelete(Builder $query): array;

    abstract public function wrap(string $identifier): string;

    public function wrapColumns(array $columns): string
    {
        return implode(', ', array_map(fn($col) => $col === '*' ? '*' : $this->wrap($col), $columns));
    }

    public function compileWhere(WhereExpressionInterface $where): array
    {
        return match ($where::class) {
            BasicWhereExpression::class => $this->compileBasicWhere($where),
            InWhereExpression::class => $this->compileInWhere($where),
            NullWhereExpression::class => $this->compileNullWhere($where),
            BetweenWhereExpression::class => $this->compileBetweenWhere($where),
            ColumnWhereExpression::class => $this->compileColumnWhere($where),
            DateWhereExpression::class => $this->compileDateWhere($where),
            DatePartWhereExpression::class => $this->compileDatePartWhere($where),
            ExistsWhereExpression::class => $this->compileExistsWhere($where),
            NestedWhereExpression::class => $this->compileNestedWhere($where),
            RawWhereExpression::class => $this->compileRawWhere($where),
            default => throw new \InvalidArgumentException("Unknown where expression: " . $where::class),
        };
    }

    private function compileBasicWhere(BasicWhereExpression $where): array
    {
        return [
            'sql' => "{$this->wrap($where->getColumn())} {$where->getOperator()} ?",
            'bindings' => [$where->getValue()],
        ];
    }

    private function compileInWhere(InWhereExpression $where): array
    {
        $values = $where->getValues();
        $placeholder = implode(', ', array_fill(0, count($values), '?'));
        $keyword = $where->isNot() ? 'NOT IN' : 'IN';

        return [
            'sql' => "{$this->wrap($where->getColumn())} {$keyword} ({$placeholder})",
            'bindings' => $values,
        ];
    }

    private function compileNullWhere(NullWhereExpression $where): array
    {
        $keyword = $where->isNot() ? 'IS NOT NULL' : 'IS NULL';

        return [
            'sql' => "{$this->wrap($where->getColumn())} {$keyword}",
            'bindings' => [],
        ];
    }

    private function compileBetweenWhere(BetweenWhereExpression $where): array
    {
        $keyword = $where->isNot() ? 'NOT BETWEEN' : 'BETWEEN';

        return [
            'sql' => "{$this->wrap($where->getColumn())} {$keyword} ? AND ?",
            'bindings' => [$where->getMin(), $where->getMax()],
        ];
    }

    private function compileColumnWhere(ColumnWhereExpression $where): array
    {
        return [
            'sql' => "{$this->wrap($where->getColumn1())} {$where->getOperator()} {$this->wrap($where->getColumn2())}",
            'bindings' => [],
        ];
    }

    private function compileDateWhere(DateWhereExpression $where): array
    {
        return [
            'sql' => "DATE({$this->wrap($where->getColumn())}) {$where->getOperator()} ?",
            'bindings' => [$where->getValue()],
        ];
    }

    private function compileDatePartWhere(DatePartWhereExpression $where): array
    {
        return [
            'sql' => "{$where->getPart()}({$this->wrap($where->getColumn())}) {$where->getOperator()} ?",
            'bindings' => [$where->getValue()],
        ];
    }

    private function compileExistsWhere(ExistsWhereExpression $where): array
    {
        $prefix = $where->isNot() ? 'NOT ' : '';

        return [
            'sql' => "{$prefix}EXISTS ({$where->getQuery()->toSql()})",
            'bindings' => $where->getQuery()->getBindings(),
        ];
    }

    private function compileNestedWhere(NestedWhereExpression $where): array
    {
        return [
            'sql' => "({$where->getQuery()->toSql()})",
            'bindings' => $where->getQuery()->getBindings(),
        ];
    }

    private function compileRawWhere(RawWhereExpression $where): array
    {
        return [
            'sql' => $where->getSql(),
            'bindings' => $where->getBindings(),
        ];
    }

    public function compileWheres(Builder $query): array
    {
        $sql = '';
        $bindings = [];

        foreach ($query->getWheres() as $i => $where) {
            $compiled = $this->compileWhere($where);
            $clause = $compiled['sql'];

            if ($i > 0) {
                $clause = $where->getBoolean() . ' ' . $clause;
            }

            $sql .= $clause;
            $bindings = array_merge($bindings, $compiled['bindings']);
        }

        return ['sql' => $sql, 'bindings' => $bindings];
    }

    public function compileJoins(Builder $query): string
    {
        $sql = '';

        foreach ($query->getJoins() as $join) {
            $table = $this->wrap($join->table);
            $first = $this->wrap($join->first);
            $second = $this->wrap($join->second);
            $sql .= " {$join->getType()} JOIN {$table} ON {$first} {$join->operator} {$second}";
        }

        return $sql;
    }

    public function compileOrderBy(Builder $query): string
    {
        $parts = [];

        foreach ($query->getOrders() as $order) {
            if ($order->raw) {
                $parts[] = $order->rawSql;
            } else {
                $parts[] = $this->wrap($order->column) . ' ' . $order->direction;
            }
        }

        return empty($parts) ? '' : ' ORDER BY ' . implode(', ', $parts);
    }

    public function compileGroupBy(Builder $query): string
    {
        $parts = [];

        foreach ($query->getGroups() as $group) {
            if ($group->raw) {
                $parts[] = $group->expression;
            } else {
                $parts[] = $this->wrap($group->expression);
            }
        }

        return empty($parts) ? '' : ' GROUP BY ' . implode(', ', $parts);
    }

    public function compileHaving(Builder $query): string
    {
        $havings = $query->getHavings();

        if (empty($havings)) {
            return '';
        }

        $parts = [];

        foreach ($havings as $having) {
            $parts[] = $having->sql;
        }

        return ' HAVING ' . implode(' AND ', $parts);
    }

    public function compileLimit(Builder $query): string
    {
        $limit = $query->getLimit();

        return $limit !== null ? " LIMIT {$limit}" : '';
    }

    public function compileOffset(Builder $query): string
    {
        $offset = $query->getOffset();

        return $offset !== null ? " OFFSET {$offset}" : '';
    }

    public function compileLock(Builder $query): string
    {
        $lock = $query->getLock();

        return $lock !== null ? " {$lock}" : '';
    }

    public function compileSelectColumns(Builder $query): string
    {
        $distinct = $query->isDistinct() ? 'DISTINCT ' : '';

        $columns = $query->getColumns();
        if (empty($columns)) {
            return "{$distinct}*";
        }

        $formatted = [];

        foreach ($columns as $select) {
            if ($select->alias !== null) {
                $formatted[] = $this->wrap($select->column) . ' AS ' . $this->wrap($select->alias);
            } else {
                $formatted[] = $select->column === '*' ? '*' : $this->wrap($select->column);
            }
        }

        return $distinct . implode(', ', $formatted);
    }
}