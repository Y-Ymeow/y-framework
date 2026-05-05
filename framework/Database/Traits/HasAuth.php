<?php

declare(strict_types=1);

namespace Framework\Database\Traits;

use Framework\Auth\Authenticatable;

/**
 * HasAuth 认证 Trait
 *
 * 让 Model 实现 Authenticatable 接口，支持密码哈希、remember token 等。
 *
 * ## 使用方式
 *
 * class User extends Model
 * {
 *     use \Framework\Database\Traits\HasAuth;
 *
 *     protected array $fillable = ['name', 'email', 'password', 'remember_token'];
 *     protected string $passwordField = 'password';
 * }
 */
trait HasAuth
{
    protected string $authIdentifierName = 'id';
    protected string $passwordField = 'password';
    protected string $rememberTokenName = 'remember_token';

    public function getAuthIdentifier(): mixed
    {
        return $this->getKey();
    }

    public function getAuthIdentifierName(): string
    {
        return $this->authIdentifierName;
    }

    public function getAuthPassword(): string
    {
        return $this->{$this->passwordField};
    }

    public function getRememberToken(): ?string
    {
        return $this->{$this->rememberTokenName} ?? null;
    }

    public function setRememberToken(string $value): void
    {
        $this->{$this->rememberTokenName} = $value;
    }

    public function getRememberTokenName(): string
    {
        return $this->rememberTokenName;
    }

    public static function findByEmail(string $email): ?static
    {
        return static::where('email', $email)->first();
    }

    public static function findByToken(string $token): ?static
    {
        return static::where('remember_token', $token)->first();
    }

    public function verifyPassword(string $plainPassword): bool
    {
        return password_verify($plainPassword, $this->getAuthPassword());
    }

    public function generateToken(): string
    {
        $token = hash('sha256', bin2hex(random_bytes(32)));
        $this->setRememberToken($token);
        if ($this->exists) {
            $this->save();
        }
        return $token;
    }

    public function clearToken(): void
    {
        $this->setRememberToken('');
        if ($this->exists) {
            $this->save();
        }
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
}
