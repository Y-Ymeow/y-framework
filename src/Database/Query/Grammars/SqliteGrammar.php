<?php

declare(strict_types=1);

namespace Framework\Database\Query\Grammars;

use Framework\Database\Query\Builder;
use Framework\Database\Query\Grammar;

class SqliteGrammar extends Grammar
{
    public function wrap(string $identifier): string
    {
        if ($identifier === '*') {
            return '*';
        }

        $parts = explode('.', $identifier);
        $wrapped = array_map(fn(string $part): string => '"' . str_replace('"', '""', trim($part, '"')) . '"', $parts);

        return implode('.', $wrapped);
    }

    public function compileSelect(Builder $query): array
    {
        $sql = 'SELECT ' . $this->compileSelectColumns($query);
        $sql .= ' FROM ' . $this->wrap($query->getFrom());
        $sql .= $this->compileJoins($query);

        $wheres = $this->compileWheres($query);
        if ($wheres['sql'] !== '') {
            $sql .= ' WHERE ' . $wheres['sql'];
        }

        $sql .= $this->compileGroupBy($query);
        $sql .= $this->compileHaving($query);
        $sql .= $this->compileOrderBy($query);
        $sql .= $this->compileLimit($query);
        $sql .= $this->compileOffset($query);
        $sql .= $this->compileLock($query);

        return ['sql' => $sql, 'bindings' => $wheres['bindings']];
    }

    public function compileInsert(Builder $query, array $data): array
    {
        $columns = implode(', ', array_map(fn(string $col): string => $this->wrap($col), array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = 'INSERT INTO ' . $this->wrap($query->getFrom()) . " ({$columns}) VALUES ({$placeholders})";

        return ['sql' => $sql, 'bindings' => array_values($data)];
    }

    public function compileUpdate(Builder $query, array $data): array
    {
        $sets = implode(', ', array_map(
            fn(string $col): string => $this->wrap($col) . ' = ?',
            array_keys($data)
        ));

        $sql = 'UPDATE ' . $this->wrap($query->getFrom()) . " SET {$sets}";

        $wheres = $this->compileWheres($query);
        if ($wheres['sql'] !== '') {
            $sql .= ' WHERE ' . $wheres['sql'];
        }

        return ['sql' => $sql, 'bindings' => array_merge(array_values($data), $wheres['bindings'])];
    }

    public function compileDelete(Builder $query): array
    {
        $sql = 'DELETE FROM ' . $this->wrap($query->getFrom());

        $wheres = $this->compileWheres($query);
        if ($wheres['sql'] !== '') {
            $sql .= ' WHERE ' . $wheres['sql'];
        }

        return ['sql' => $sql, 'bindings' => $wheres['bindings']];
    }
}