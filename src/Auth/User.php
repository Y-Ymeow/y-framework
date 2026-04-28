<?php

declare(strict_types=1);

namespace Framework\Auth;

use Framework\Database\Model;

class User extends Model
{
    protected string $table = 'users';
    protected array $fillable = ['name', 'email', 'password'];
    protected array $hidden = ['password', 'remember_token'];
    protected array $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public function setPasswordAttribute(string $value): void
    {
        $this->attributes['password'] = $this->hashPassword($value);
    }

    public static function findByEmail(string $email): ?self
    {
        $result = static::where('email', $email)->first();
        if (!$result) {
            return null;
        }
        return static::find($result['id']);
    }

    public static function findByToken(string $token): ?self
    {
        $result = static::where('remember_token', $token)->first();
        if (!$result) {
            return null;
        }
        return static::find($result['id']);
    }

    public function generateToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->remember_token = $token;
        $this->save();
        return $token;
    }

    public function clearToken(): void
    {
        $this->remember_token = null;
        $this->save();
    }
}
