<?php

declare(strict_types=1);

namespace Admin\Auth;

/**
 * Gate 授权系统
 *
 * 基于 Ability（能力）的授权检查，支持闭包、策略类和 before/after 钩子。
 *
 * ## 使用方式
 *
 * // 定义 ability（在 AuthServiceProvider 中）
 * Gate::define('update-post', function (User $user, Post $post) {
 *     return $user->id === $post->user_id;
 * });
 *
 * // 检查权限
 * if (Gate::allows('update-post', $post)) { ... }
 * if (Gate::denies('update-post', $post)) { ... }
 * Gate::authorize('update-post', $post);  // 不通过时抛异常
 *
 * // 策略类方式
 * Gate::policy(Post::class, PostPolicy::class);
 */
class Gate
{
    private static ?self $instance = null;

    private array $abilities = [];
    private array $policies = [];
    private array $beforeCallbacks = [];
    private array $afterCallbacks = [];

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function define(string $ability, callable $callback): self
    {
        $this->abilities[$ability] = $callback;
        return $this;
    }

    public function resource(string $name, string $class, array $abilities = null): self
    {
        $abilities ??= ['viewAny', 'view', 'create', 'update', 'delete', 'restore', 'forceDelete'];

        foreach ($abilities as $ability) {
            $this->define("{$name}:{$ability}", function ($user, ...$args) use ($class, $ability) {
                return $this->callPolicy($class, $ability, $user, ...$args);
            });
        }

        return $this;
    }

    public function policy(string $model, string $policy): self
    {
        $this->policies[$model] = $policy;
        return $this;
    }

    public function before(callable $callback): self
    {
        $this->beforeCallbacks[] = $callback;
        return $this;
    }

    public function after(callable $callback): self
    {
        $this->afterCallbacks[] = $callback;
        return $this;
    }

    public function allows(string $ability, mixed ...$arguments): bool
    {
        return $this->inspect($ability, ...$arguments)->allowed();
    }

    public function denies(string $ability, mixed ...$arguments): bool
    {
        return !$this->allows($ability, ...$arguments);
    }

    public function check(iterable|string $abilities, mixed ...$arguments): bool
    {
        foreach ((array) $abilities as $ability) {
            if (!$this->allows($ability, ...$arguments)) {
                return false;
            }
        }
        return true;
    }

    public function any(iterable|string $abilities, mixed ...$arguments): bool
    {
        foreach ((array) $abilities as $ability) {
            if ($this->allows($ability, ...$arguments)) {
                return true;
            }
        }
        return false;
    }

    public function none(iterable|string $abilities, mixed ...$arguments): bool
    {
        return !$this->any($abilities, ...$arguments);
    }

    public function authorize(string $ability, mixed ...$arguments): bool
    {
        $result = $this->inspect($ability, ...$arguments);

        if (!$result->allowed()) {
            throw new \RuntimeException(
                $result->message() ?: "Unauthorized: {$ability}",
                403
            );
        }

        return true;
    }

    public function inspect(string $ability, mixed ...$arguments): Response
    {
        $user = $arguments[0] ?? auth()->user();

        if (!$user) {
            return new Response(false, 'Unauthenticated.');
        }

        foreach ($this->beforeCallbacks as $callback) {
            $result = $callback($user, $ability, $arguments);

            if ($result !== null) {
                return $result instanceof Response ? $result : (
                    $result === true ? new Response(true) : new Response(false)
                );
            }
        }

        $result = $this->callAbilityCallback($ability, $user, $arguments);

        foreach ($this->afterCallbacks as $callback) {
            $callback($user, $ability, $result, $arguments);
        }

        return $result instanceof Response ? $result : (
            $result === true ? new Response(true) : new Response(false)
        );
    }

    public function forUser(?Authenticatable $user): self
    {
        return new ForUserGate($this, $user);
    }

    protected function callAbilityCallback(string $ability, Authenticatable $user, array $arguments): Response|bool|null
    {
        if (isset($this->abilities[$ability])) {
            $callback = $this->abilities[$ability];
            $result = $callback($user, ...$arguments);

            if ($result instanceof Response) {
                return $result;
            }

            return $result;
        }

        if (!empty($arguments) && is_object($arguments[1] ?? null)) {
            $model = $arguments[1];
            $className = get_class($model);

            if (isset($this->policies[$className])) {
                return $this->callPolicy($this->policies[$className], $ability, $user, ...$arguments);
            }

            $guessedPolicy = str_replace('\\Models\\', '\\Policies\\', $className) . 'Policy';
            if (class_exists($guessedPolicy)) {
                return $this->callPolicy($guessedPolicy, $ability, $user, ...$arguments);
            }
        }

        return false;
    }

    protected function callPolicy(string $policyClass, string $ability, Authenticatable $user, mixed ...$arguments): bool
    {
        $policy = new $policyClass();

        $method = $this->normalizeAbilityToMethod($ability);

        if (method_exists($policy, $method)) {
            return $policy->$method($user, ...$arguments);
        }

        if (method_exists($policy, '__invoke')) {
            return $policy($user, $ability, ...$arguments);
        }

        return false;
    }

    private function normalizeAbilityToMethod(string $ability): string
    {
        return match ($ability) {
            'create' => 'create',
            default => $ability,
        };
    }

    public static function defineStatic(string $ability, callable $callable): void
    {
        self::getInstance()->define($ability, $callable);
    }

    public static function policyStatic(string $model, string $policy): void
    {
        self::getInstance()->policy($model, $policy);
    }

    public static function allowsStatic(string $ability, mixed ...$arguments): bool
    {
        return self::getInstance()->allows($ability, ...$arguments);
    }

    public static function deniesStatic(string $ability, mixed ...$arguments): bool
    {
        return self::getInstance()->denies($ability, ...$arguments);
    }
}
