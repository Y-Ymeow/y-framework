<?php

declare(strict_types=1);

namespace Framework\Http\Session;

/**
 * Session 管理类
 *
 * 直接操作 PHP 原生 session，使用 $_SESSION 存储数据。
 * 保持与旧系统 Framework\Http\Session 完全兼容的行为。
 */
class Session
{
    private bool $started = false;

    private function ensureSession(): void
    {
        if ($this->started || session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $sessionPath = getenv('SESSION_PATH') ?: $_ENV['SESSION_PATH'] ?? null;
        if (!$sessionPath) {
            $sessionPath = base_path('storage/sessions');
        }
        ini_set('session.save_path', $sessionPath);

        session_start();
        $this->started = true;
    }

    public function start(): void
    {
        $this->ensureSession();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->ensureSession();
        return $_SESSION[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->ensureSession();
        $_SESSION[$key] = $value;
    }

    public function has(string $key): bool
    {
        $this->ensureSession();
        if (!isset($_SESSION)) {
            return false;
        }
        return array_key_exists($key, $_SESSION);
    }

    public function remove(string $key): void
    {
        $this->ensureSession();
        unset($_SESSION[$key]);
    }

    public function flash(string $key, mixed $value): void
    {
        $this->ensureSession();
        $_SESSION['_flash'][$key] = $value;
    }

    public function getFlash(string $key, mixed $default = null): mixed
    {
        $this->ensureSession();
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $value;
    }

    public function hasFlash(string $key): bool
    {
        $this->ensureSession();
        return isset($_SESSION['_flash'][$key]);
    }

    public function all(): array
    {
        $this->ensureSession();
        return $_SESSION ?? [];
    }

    public function clear(): void
    {
        $this->ensureSession();
        $_SESSION = [];
    }

    public function destroy(): void
    {
        $this->ensureSession();
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
        $this->ensureSession();
        session_regenerate_id(true);
    }

    public function getId(): string
    {
        $this->ensureSession();
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

    public function close(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        $this->started = false;
    }

    public function isActive(): bool
    {
        return session_status() === PHP_SESSION_ACTIVE;
    }

    public static function gc(?int $maxLifetime = null): int
    {
        $savePath = ini_get('session.save_path');
        if (empty($savePath) || !is_dir($savePath)) {
            return 0;
        }

        $maxLifetime ??= (int)ini_get('session.gc_maxlifetime') ?: 1440;
        $now = time();
        $count = 0;

        $files = glob($savePath . '/sess_*');
        if ($files === false) {
            $files = glob($savePath . '/*') ?: [];
        }
        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file) > $maxLifetime)) {
                @unlink($file);
                $count++;
            }
        }

        return $count;
    }
}