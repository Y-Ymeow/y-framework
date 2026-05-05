<?php

declare(strict_types=1);

namespace Admin\Auth;

interface Authenticatable
{
    public function getAuthIdentifier(): mixed;

    public function getAuthIdentifierName(): string;

    public function getAuthPassword(): string;

    public function getRememberToken(): ?string;

    public function setRememberToken(string $value): void;

    public function getRememberTokenName(): string;
}
