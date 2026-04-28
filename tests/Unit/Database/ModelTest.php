<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use PHPUnit\Framework\TestCase;
use Framework\Database\Model;
use Framework\Database\Connection;

class TestUser extends Model
{
    protected string $table = 'test_users';
    protected array $fillable = ['name', 'email', 'password'];
    protected array $hidden = ['password'];
    protected array $casts = ['is_admin' => 'bool', 'settings' => 'array'];
}

class ModelTest extends TestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = Connection::make([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        Model::setConnection($this->connection);

        $this->connection->execute("
            CREATE TABLE test_users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                password VARCHAR(255) NOT NULL,
                is_admin INTEGER DEFAULT 0,
                settings TEXT,
                created_at TIMESTAMP,
                updated_at TIMESTAMP
            )
        ");
    }

    public function test_create_model(): void
    {
        $user = TestUser::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret',
        ]);

        $this->assertNotNull($user->id);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
    }

    public function test_find_model(): void
    {
        TestUser::create(['name' => 'Jane', 'email' => 'jane@example.com', 'password' => 'secret']);

        $user = TestUser::find(1);

        $this->assertNotNull($user);
        $this->assertEquals('Jane', $user->name);
    }

    public function test_update_model(): void
    {
        $user = TestUser::create(['name' => 'Bob', 'email' => 'bob@example.com', 'password' => 'secret']);

        $user->name = 'Robert';
        $user->save();

        $updated = TestUser::find($user->id);
        $this->assertEquals('Robert', $updated->name);
    }

    public function test_delete_model(): void
    {
        $user = TestUser::create(['name' => 'ToDelete', 'email' => 'delete@example.com', 'password' => 'secret']);

        $id = $user->id;
        $user->delete();

        $this->assertNull(TestUser::find($id));
    }

    public function test_where_clause(): void
    {
        TestUser::create(['name' => 'Alice', 'email' => 'alice@example.com', 'password' => 'secret']);
        TestUser::create(['name' => 'Alice Smith', 'email' => 'alice2@example.com', 'password' => 'secret']);

        $users = TestUser::where('name', 'Alice')->get();

        $this->assertCount(1, $users);
        $this->assertEquals('alice@example.com', $users[0]['email']);
    }

    public function test_fillable_protection(): void
    {
        $user = new TestUser();
        $user->fill([
            'name' => 'Test',
            'email' => 'test@example.com',
            'password' => 'secret',
            'is_admin' => true,
        ]);

        $this->assertEquals('Test', $user->name);
        $this->assertNull($user->is_admin);
    }

    public function test_hidden_attributes(): void
    {
        $user = TestUser::create(['name' => 'Hidden', 'email' => 'hidden@example.com', 'password' => 'secret']);

        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayHasKey('email', $array);
    }

    public function test_cast_attributes(): void
    {
        $user = new TestUser();
        $user->is_admin = 1;
        $user->settings = '{"theme":"dark"}';

        $this->assertTrue($user->is_admin);
        $this->assertIsArray($user->settings);
        $this->assertEquals('dark', $user->settings['theme']);
    }

    public function test_to_json(): void
    {
        $user = TestUser::create(['name' => 'JSON', 'email' => 'json@example.com', 'password' => 'secret']);

        $json = $user->toJson();

        $decoded = json_decode($json, true);
        $this->assertEquals('JSON', $decoded['name']);
        $this->assertArrayNotHasKey('password', $decoded);
    }

    public function test_find_or_fail_throws_exception(): void
    {
        $this->expectException(\RuntimeException::class);
        TestUser::findOrFail(9999);
    }

    public function test_all_returns_all_records(): void
    {
        TestUser::create(['name' => 'User1', 'email' => 'user1@example.com', 'password' => 'secret']);
        TestUser::create(['name' => 'User2', 'email' => 'user2@example.com', 'password' => 'secret']);

        $users = TestUser::all();

        $this->assertCount(2, $users);
    }
}
