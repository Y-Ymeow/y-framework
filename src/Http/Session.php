<?php

declare(strict_types=1);

namespace Framework\Http;

class Session
{
    private bool $started = false;

    public function start(): void
    {
        if ($this->started || session_status() === PHP_SESSION_ACTIVE) return;

        $sessionPath = getenv('SESSION_PATH') ?: $_ENV['SESSION_PATH'] ?? null;
        if (!$sessionPath) {
            $sessionPath = '/computer/Project/frameworks/php/storage/sessions';
        }
        ini_set('session.save_path', $sessionPath);

        session_start();
        $this->started = true;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->start();
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->start();
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        $this->start();
        return $_SESSION ? array_key_exists($key, $_SESSION) : false;
    }

    public function remove(string $key): void
    {
        $this->start();
        unset($_SESSION[$key]);
    }

    public function flash(string $key, mixed $value): void
    {
        $this->start();
        $_SESSION['_flash'][$key] = $value;
    }

    public function getFlash(string $key, mixed $default = null): mixed
    {
        $this->start();
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    public function hasFlash(string $key): bool
    {
        $this->start();
        return isset($_SESSION['_flash'][$key]);
    }

    public function all(): array
    {
        $this->start();
        return $_SESSION;
    }

    public function clear(): void
    {
        $this->start();
        $_SESSION = [];
    }

    public function destroy(): void
    {
        $this->start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
        $this->started = false;
    }

    public function regenerate(): void
    {
        $this->start();
        session_regenerate_id(true);
    }

    public function getId(): string
    {
        $this->start();
        return session_id();
    }

    public function token(): string
    {
        if (!$this->has('_token')) {
            $this->set('_token', bin2hex(random_bytes(32)));
        }
        return $this->get('_token');
    }

    public function verifyToken(string $token): bool
    {
        return hash_equals($this->get('_token', ''), $token);
    }

    /**
     * 关闭 Session（释放锁）
     *
     * 用于长连接场景（如 SSE），避免阻塞其他请求
     * 数据仍然保存在 $_SESSION 中可读，但不能再写入
     */
    public function close(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
    }

    /**
     * 检查 Session 是否活跃
     */
    public function isActive(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }
}
