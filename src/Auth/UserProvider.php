<?php

declare(strict_types=1);

namespace Framework\Auth;

interface UserProvider
{
    public function retrieveById(mixed $id): ?Authenticatable;

    public function retrieveByToken(string $identifier, string $token): ?Authenticatable;

    public function retrieveByCredentials(array $credentials): ?Authenticatable;

    public function validateCredentials(Authenticatable $user, array $credentials): bool;

    public function updateRememberToken(Authenticatable $user, string $token): void;
}
