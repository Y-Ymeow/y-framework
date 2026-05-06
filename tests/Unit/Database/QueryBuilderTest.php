<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use Framework\Database\Connection\Connection;
use Framework\Database\Query\Builder;
use Framework\Database\Query\Grammars\SqliteGrammar;
use PDO;

class QueryBuilderTest extends \PHPUnit\Framework\TestCase
{
    private Connection $connection;
    private Builder $qb;

    protected function setUp(): void
    {
        $pdo = new PDO('sqlite::memory:', '', '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $pdo->exec("CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT, created_at DATETIME)");
        $pdo->exec("INSERT INTO users (name, email, created_at) VALUES ('John Doe', 'john@example.com', '2024-01-01 00:00:00')");
        $pdo->exec("INSERT INTO users (name, email, created_at) VALUES ('Jane Smith', 'jane@example.com', '2024-01-02 00:00:00')");
        $this->connection = new Connection($pdo, '', 'sqlite');
        $this->qb = new Builder($this->connection, 'users', new SqliteGrammar());
    }

    public function test_select_all(): void
    {
        $results = $this->qb->select('*')->get();
        $this->assertCount(2, $results);
    }

    public function test_select_specific_columns(): void
    {
        $results = $this->qb->select('id', 'name')->get();
        $this->assertArrayHasKey('id', $results[0]);
        $this->assertArrayHasKey('name', $results[0]);
        $this->assertArrayNotHasKey('email', $results[0]);
    }

    public function test_where_basic(): void
    {
        $results = $this->qb->where('name', 'John Doe')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('John Doe', $results[0]['name']);
    }

    public function test_where_with_operator(): void
    {
        $results = $this->qb->where('id', '>', 1)->get();
        $this->assertCount(1, $results);
        $this->assertEquals(2, $results[0]['id']);
    }

    public function test_where_in(): void
    {
        $results = $this->qb->whereIn('id', [1, 3])->get();
        $this->assertCount(1, $results);
    }

    public function test_where_null(): void
    {
        $this->connection->execute("INSERT INTO users (name, email) VALUES ('No Date', 'nodate@example.com')");
        $results = $this->qb->whereNull('created_at')->get();
        $this->assertCount(1, $results);
    }

    public function test_where_not_null(): void
    {
        $results = $this->qb->whereNotNull('created_at')->get();
        $this->assertCount(2, $results);
    }

    public function test_order_by_asc(): void
    {
        $results = $this->qb->orderBy('name', 'ASC')->get();
        $this->assertEquals('Jane Smith', $results[0]['name']);
        $this->assertEquals('John Doe', $results[1]['name']);
    }

    public function test_order_by_desc(): void
    {
        $results = $this->qb->orderBy('name', 'DESC')->get();
        $this->assertEquals('John Doe', $results[0]['name']);
    }

    public function test_order_by_invalid_direction(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->qb->orderBy('name', 'INVALID')->get();
    }

    public function test_limit_and_offset(): void
    {
        $results = $this->qb->limit(1)->offset(1)->get();
        $this->assertCount(1, $results);
    }

    public function test_first(): void
    {
        $result = $this->qb->first();
        $this->assertNotNull($result);
        $this->assertEquals('John Doe', $result['name']);
    }

    public function test_count(): void
    {
        $count = $this->qb->count();
        $this->assertEquals(2, $count);
    }

    public function test_sum(): void
    {
        $this->connection->execute("ALTER TABLE users ADD COLUMN age INTEGER DEFAULT 0");
        $this->connection->execute("UPDATE users SET age = 25 WHERE id = 1");
        $this->connection->execute("UPDATE users SET age = 30 WHERE id = 2");

        $sum = $this->qb->sum('age');
        $this->assertEquals(55, $sum);
    }

    public function test_max(): void
    {
        $this->connection->execute("ALTER TABLE users ADD COLUMN age INTEGER DEFAULT 0");
        $this->connection->execute("UPDATE users SET age = 25 WHERE id = 1");
        $this->connection->execute("UPDATE users SET age = 30 WHERE id = 2");

        $max = $this->qb->max('age');
        $this->assertEquals(30, $max);
    }

    public function test_min(): void
    {
        $this->connection->execute("ALTER TABLE users ADD COLUMN age INTEGER DEFAULT 0");
        $this->connection->execute("UPDATE users SET age = 25 WHERE id = 1");
        $this->connection->execute("UPDATE users SET age = 30 WHERE id = 2");

        $min = $this->qb->min('age');
        $this->assertEquals(25, $min);
    }

    public function test_to_sql(): void
    {
        $sql = $this->qb->where('name', 'John')->toSql();
        $this->assertStringContainsString('SELECT * FROM "users"', $sql);
        $this->assertStringContainsString('WHERE "name" = ?', $sql);
    }

    public function test_insert(): void
    {
        $result = $this->qb->insert([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'created_at' => '2024-01-03 00:00:00'
        ]);
        $this->assertGreaterThan(0, $result);

        $user = $this->qb->where('email', 'test@example.com')->first();
        $this->assertEquals('Test User', $user['name']);
    }

    public function test_update(): void
    {
        $affected = $this->qb->where('id', 1)->update(['name' => 'Updated Name']);
        $this->assertEquals(1, $affected);

        $user = $this->qb->where('id', 1)->first();
        $this->assertEquals('Updated Name', $user['name']);
    }

    public function test_delete(): void
    {
        $deleted = $this->qb->where('id', 1)->delete();
        $this->assertEquals(1, $deleted);

        $count = (new Builder($this->connection, 'users', new SqliteGrammar()))->count();
        $this->assertEquals(1, $count);
    }

    public function test_sql_injection_protection_column(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->qb->where('id; DROP TABLE users', '=', 1)->get();
    }

    public function test_sql_injection_protection_operator(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->qb->where('id', '=; DROP TABLE', 1)->get();
    }

    public function test_sql_injection_protection_order_direction(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->qb->orderBy('name', 'ASC; DROP TABLE')->get();
    }

    public function test_group_by(): void
    {
        $sql = $this->qb->groupBy('name')->toSql();
        $this->assertStringContainsString('GROUP BY "name"', $sql);
    }

    public function test_having(): void
    {
        $sql = $this->qb->having('id', '>', 1)->toSql();
        $this->assertStringContainsString('HAVING "id" > ?', $sql);
    }
}