<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use PHPUnit\Framework\TestCase;
use Framework\Auth\User;
use Framework\Auth\AuthManager;
use Framework\Database\Connection;
use Framework\Database\Model;
use Framework\Http\Session;

class AuthTest extends TestCase
{
    private Connection $connection;
    private Session $session;
    private AuthManager $auth;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = Connection::make([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        Model::setConnection($this->connection);

        $this->connection->execute("
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                password VARCHAR(255) NOT NULL,
                remember_token VARCHAR(100),
                created_at TIMESTAMP,
                updated_at TIMESTAMP
            )
        ");

        $this->session = $this->createMock(Session::class);
        $this->auth = new AuthManager($this->session, $this->connection);
    }

    public function test_password_hashing(): void
    {
        $user = new User();
        $password = 'my-secret-password';
        $hash = $user->hashPassword($password);

        $this->assertTrue(password_verify($password, $hash));
        $this->assertFalse(password_verify('wrong-password', $hash));
    }

    public function test_password_verification(): void
    {
        $user = new User();
        $password = 'test-password';
        $user->password = $user->hashPassword($password);

        $this->assertTrue($user->verifyPassword($password));
        $this->assertFalse($user->verifyPassword('wrong-password'));
    }

    public function test_find_by_email(): void
    {
        $hash = password_hash('secret', PASSWORD_BCRYPT);
        $this->connection->insert('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => $hash,
        ]);

        $user = User::findByEmail('test@example.com');

        $this->assertNotNull($user);
        $this->assertEquals('Test User', $user->name);
    }

    public function test_find_by_email_returns_null_for_nonexistent(): void
    {
        $user = User::findByEmail('nonexistent@example.com');

        $this->assertNull($user);
    }

    public function test_generate_token(): void
    {
        $hash = password_hash('secret', PASSWORD_BCRYPT);
        $id = $this->connection->insert('users', [
            'name' => 'Token User',
            'email' => 'token@example.com',
            'password' => $hash,
        ]);

        $user = User::find($id);
        $token = $user->generateToken();

        $this->assertEquals(64, strlen($token));
        $this->assertEquals($token, $user->remember_token);
    }

    public function test_auth_attempt_success(): void
    {
        $password = 'correct-password';
        $hash = password_hash($password, PASSWORD_BCRYPT);
        
        $this->connection->insert('users', [
            'name' => 'Auth User',
            'email' => 'auth@example.com',
            'password' => $hash,
        ]);

        $this->session->method('set');
        $this->session->method('regenerate');
        $this->session->method('get')->willReturn(1);

        $result = $this->auth->attempt([
            'email' => 'auth@example.com',
            'password' => $password,
        ]);

        $this->assertTrue($result);
    }

    public function test_auth_attempt_fails_with_wrong_password(): void
    {
        $hash = password_hash('correct-password', PASSWORD_BCRYPT);
        
        $this->connection->insert('users', [
            'name' => 'Auth User',
            'email' => 'auth2@example.com',
            'password' => $hash,
        ]);

        $result = $this->auth->attempt([
            'email' => 'auth2@example.com',
            'password' => 'wrong-password',
        ]);

        $this->assertFalse($result);
    }

    public function test_auth_attempt_fails_with_nonexistent_user(): void
    {
        $result = $this->auth->attempt([
            'email' => 'nonexistent@example.com',
            'password' => 'any-password',
        ]);

        $this->assertFalse($result);
    }

    public function test_check_returns_true_when_authenticated(): void
    {
        $this->session->method('get')->with('auth_id')->willReturn(1);

        $this->assertTrue($this->auth->check());
    }

    public function test_check_returns_false_when_not_authenticated(): void
    {
        $this->session->method('get')->with('auth_id')->willReturn(null);

        $this->assertFalse($this->auth->check());
    }

    public function test_guest_returns_opposite_of_check(): void
    {
        $this->session->method('get')->with('auth_id')->willReturn(null);

        $this->assertTrue($this->auth->guest());
    }

    public function test_user_model_hidden_password(): void
    {
        $user = new User();
        $user->name = 'Test';
        $user->email = 'test@example.com';
        $user->password = 'hashed_password';

        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
    }
}
