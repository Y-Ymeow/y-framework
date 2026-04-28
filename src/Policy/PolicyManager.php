<?php

declare(strict_types=1);

namespace Framework\Policy;

use Framework\Auth\Auth;
use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class Policy
{
    public function __construct(
        public string $ability = '',
        public ?string $message = null,
    ) {}
}

class PolicyManager
{
    private array $policies = [];
    private ?Auth $auth = null;

    public function __construct(?Auth $auth = null)
    {
        $this->auth = $auth;
    }

    public function define(string $ability, callable $callback): self
    {
        $this->policies[$ability] = $callback;
        return $this;
    }

    public function allows(string $ability, mixed $arguments = null): bool
    {
        return $this->check($ability, $arguments);
    }

    public function denies(string $ability, mixed $arguments = null): bool
    {
        return !$this->check($ability, $arguments);
    }

    public function check(string $ability, mixed $arguments = null): bool
    {
        if (!isset($this->policies[$ability])) {
            return false;
        }

        $callback = $this->policies[$ability];
        $user = $this->auth?->user();

        return $callback($user, $arguments);
    }

    public function authorize(string $ability, mixed $arguments = null): void
    {
        if ($this->denies($ability, $arguments)) {
            $message = "You are not authorized to perform [{$ability}].";
            throw new \Framework\Http\HttpException(403, $message);
        }
    }

    public function any(array $abilities, mixed $arguments = null): bool
    {
        foreach ($abilities as $ability) {
            if ($this->check($ability, $arguments)) return true;
        }
        return false;
    }

    public function none(array $abilities, mixed $arguments = null): bool
    {
        foreach ($abilities as $ability) {
            if ($this->check($ability, $arguments)) return false;
        }
        return true;
    }

    public function before(callable $callback): self
    {
        $this->policies['__before'] = $callback;
        return $this;
    }
}
