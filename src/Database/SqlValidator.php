<?php

declare(strict_types=1);

namespace Framework\Database;

class SqlValidator
{
    private const ALLOWED_OPERATORS = [
        '=', '!=', '<', '>', '<=', '>=',
        '<>', 'like', 'not like', 'ilike',
        'in', 'not in', 'between', 'not between',
        'is', 'is not', 'exists', 'not exists',
    ];

    private const ALLOWED_DIRECTIONS = ['ASC', 'DESC'];

    private const COLUMN_PATTERN = '/^[a-zA-Z_][a-zA-Z0-9_]*$/';
    private const TABLE_PATTERN = '/^[a-zA-Z_][a-zA-Z0-9_]*$/';
    private const ALIAS_PATTERN = '/^[a-zA-Z_][a-zA-Z0-9_]*$/';

    public static function validateColumn(string $column): string
    {
        $column = trim($column);

        // 支持 table.column 格式
        $parts = explode('.', $column);
        foreach ($parts as $part) {
            $part = trim($part, '`');
            if ($part !== '*' && !preg_match(self::COLUMN_PATTERN, $part)) {
                throw new \InvalidArgumentException(
                    "Invalid column name: {$column}. Only alphanumeric characters and underscores are allowed."
                );
            }
        }

        return $column;
    }

    public static function validateTable(string $table): string
    {
        $table = trim($table, '`');
        if (!preg_match(self::TABLE_PATTERN, $table)) {
            throw new \InvalidArgumentException(
                "Invalid table name: {$table}. Only alphanumeric characters and underscores are allowed."
            );
        }

        return $table;
    }

    public static function validateOperator(string $operator): string
    {
        $operator = trim($operator);
        $normalized = strtolower($operator);
        if (!in_array($normalized, self::ALLOWED_OPERATORS, true)) {
            throw new \InvalidArgumentException(
                "Invalid operator: {$operator}. Allowed operators: " . implode(', ', self::ALLOWED_OPERATORS)
            );
        }

        return strtoupper($operator);
    }

    public static function validateDirection(string $direction): string
    {
        $direction = trim(strtoupper($direction));
        if (!in_array($direction, self::ALLOWED_DIRECTIONS, true)) {
            throw new \InvalidArgumentException(
                "Invalid sort direction: {$direction}. Must be ASC or DESC."
            );
        }

        return $direction;
    }

    public static function validateAlias(string $alias): string
    {
        $alias = trim($alias, '`');
        if (!preg_match(self::ALIAS_PATTERN, $alias)) {
            throw new \InvalidArgumentException(
                "Invalid alias: {$alias}. Only alphanumeric characters and underscores are allowed."
            );
        }

        return $alias;
    }

    public static function validateColumns(array $columns): array
    {
        $validated = [];
        foreach ($columns as $column) {
            if ($column === '*') {
                $validated[] = '*';
                continue;
            }

            // 支持 AS 别名
            $parts = preg_split('/\s+as\s+/i', trim($column));
            $col = self::validateColumn($parts[0]);

            if (isset($parts[1])) {
                $alias = self::validateAlias($parts[1]);
                $validated[] = "{$col} AS {$alias}";
            } else {
                $validated[] = $col;
            }
        }

        return $validated;
    }

    public static function escapeIdentifier(string $identifier): string
    {
        if ($identifier === '*') {
            return '*';
        }
        return '`' . str_replace('`', '``', trim($identifier, '`')) . '`';
    }
}
