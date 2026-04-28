<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use Framework\Database\SqlValidator;
use Framework\Database\QueryBuilder;

class SqlValidatorTest extends \PHPUnit\Framework\TestCase
{
    public function test_valid_column_names(): void
    {
        $this->assertEquals('id', SqlValidator::validateColumn('id'));
        $this->assertEquals('user_name', SqlValidator::validateColumn('user_name'));
        $this->assertEquals('users.id', SqlValidator::validateColumn('users.id'));
        $this->assertEquals('*', SqlValidator::validateColumn('*'));
    }

    public function test_invalid_column_names(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SqlValidator::validateColumn('id; DROP TABLE users');
    }

    public function test_valid_table_names(): void
    {
        $this->assertEquals('users', SqlValidator::validateTable('users'));
        $this->assertEquals('user_roles', SqlValidator::validateTable('user_roles'));
    }

    public function test_invalid_table_names(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SqlValidator::validateTable('users; DROP TABLE');
    }

    public function test_valid_operators(): void
    {
        $this->assertEquals('=', SqlValidator::validateOperator('='));
        $this->assertEquals('LIKE', SqlValidator::validateOperator('like'));
        $this->assertEquals('IN', SqlValidator::validateOperator('in'));
        $this->assertEquals('BETWEEN', SqlValidator::validateOperator('between'));
    }

    public function test_invalid_operators(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SqlValidator::validateOperator('=; DROP TABLE');
    }

    public function test_valid_directions(): void
    {
        $this->assertEquals('ASC', SqlValidator::validateDirection('ASC'));
        $this->assertEquals('DESC', SqlValidator::validateDirection('desc'));
    }

    public function test_invalid_directions(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        SqlValidator::validateDirection('ASC; DROP TABLE');
    }

    public function test_escape_identifier(): void
    {
        $this->assertEquals('*', SqlValidator::escapeIdentifier('*'));
        $this->assertEquals('`id`', SqlValidator::escapeIdentifier('id'));
        $this->assertEquals('`user_name`', SqlValidator::escapeIdentifier('user_name'));
        $this->assertEquals('`users``id`', SqlValidator::escapeIdentifier('users`id'));
    }

    public function test_validate_columns_with_alias(): void
    {
        $result = SqlValidator::validateColumns(['id', 'name as username', 'created_at']);
        $this->assertEquals(['id', 'name AS username', 'created_at'], $result);
    }

    public function test_validate_columns_with_wildcard(): void
    {
        $result = SqlValidator::validateColumns(['*', 'id']);
        $this->assertEquals(['*', 'id'], $result);
    }
}
