<?php

declare(strict_types=1);

namespace Framework\Auth;

class ForUserGate
{
    private Gate $gate;
    private ?Authenticatable $user;

    public function __construct(Gate $gate, ?Authenticatable $user)
    {
        $this->gate = $gate;
        $this->user = $user;
    }

    public function allows(string $ability, mixed ...$arguments): bool
    {
        return $this->gate->inspect($ability, $this->user, ...$arguments)->allowed();
    }

    public function denies(string $ability, mixed ...$arguments): bool
    {
        return !$this->allows($ability, ...$arguments);
    }

    public function check(iterable|string $abilities, mixed ...$arguments): bool
    {
        return $this->gate->check($abilities, ...$arguments);
    }

    public function any(iterable|string $abilities, mixed ...$arguments): bool
    {
        return $this->gate->any($abilities, ...$arguments);
    }

    public function none(iterable|string $abilities, mixed ...$arguments): bool
    {
        return !$this->any($abilities, ...$arguments);
    }

    public function authorize(string $ability, mixed ...$arguments): bool
    {
        return $this->gate->authorize($ability, $this->user, ...$arguments);
    }
}
