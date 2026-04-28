<?php

declare(strict_types=1);

namespace Framework\Auth;

use Framework\Database\Model;

trait Authenticatable
{
    public function getAuthIdentifierName(): string
    {
        return $this->primaryKey ?? 'id';
    }

    public function getAuthIdentifier(): mixed
    {
        return $this->{$this->getAuthIdentifierName()};
    }

    public function getAuthPassword(): string
    {
        return $this->password;
    }

    public function getRememberTokenName(): string
    {
        return 'remember_token';
    }

    public function getRememberToken(): ?string
    {
        return $this->{$this->getRememberTokenName()};
    }

    public function setRememberToken(string $value): void
    {
        $this->{$this->getRememberTokenName()} = $value;
    }
}
