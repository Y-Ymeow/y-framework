<?php

declare(strict_types=1);

namespace Framework\Http\Session;

/**
 * 文件 Session 存储
 *
 * 使用 PHP 内置 session 机制，兼容现有文件存储。
 * 通过 session_set_save_handler 或直接操作文件实现。
 */
class FileSessionStore implements SessionStoreInterface
{
    private string $savePath;

    public function __construct(?string $savePath = null)
    {
        $this->savePath = $savePath ?? $this->resolveSavePath();
    }

    public function read(string $sessionId): array
    {
        // 优先从 PHP 原生 session 中读取（与旧系统互通）
        if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION)) {
            // 本存储使用独立文件格式，但需要 CSRF token 互通时回退到 $_SESSION
            $file = $this->path($sessionId);
            if (is_file($file)) {
                $content = @file_get_contents($file);
                if ($content !== false && $content !== '') {
                    $data = @unserialize($content);
                    if (is_array($data)) {
                        return $data;
                    }
                }
                return [];
            }

            // 文件不存在时，从 $_SESSION 导入已有数据
            $data = [];
            foreach ($_SESSION as $key => $value) {
                $data[$key] = $value;
            }
            // 将数据写入文件，后续请求直接走文件
            $this->write($sessionId, $data);

            return $data;
        }

        $file = $this->path($sessionId);

        if (!is_file($file)) {
            return [];
        }

        $content = @file_get_contents($file);
        if ($content === false || $content === '') {
            return [];
        }

        $data = @unserialize($content);
        if (is_array($data)) {
            return $data;
        }

        // 最后回退：从 PHP 原生 $_SESSION 导入
        if (isset($_SESSION) && is_array($_SESSION)) {
            $data = [];
            foreach ($_SESSION as $key => $value) {
                $data[$key] = $value;
            }
            $this->write($sessionId, $data);
            return $data;
        }

        return [];
    }

    public function write(string $sessionId, array $data): void
    {
        $file = $this->path($sessionId);
        $dir = dirname($file);

        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        @file_put_contents($file, serialize($data), LOCK_EX);
    }

    public function destroy(string $sessionId): void
    {
        $file = $this->path($sessionId);

        if (is_file($file)) {
            @unlink($file);
        }
    }

    public function gc(int $maxLifetime): int
    {
        $now = time();
        $count = 0;

        $files = glob($this->savePath . '/sess_*');
        if ($files === false) {
            return 0;
        }

        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file) > $maxLifetime)) {
                @unlink($file);
                $count++;
            }
        }

        return $count;
    }

    /**
     * 获取存储路径
     */
    public function getSavePath(): string
    {
        return $this->savePath;
    }

    private function path(string $sessionId): string
    {
        return $this->savePath . '/sess_' . $sessionId;
    }

    private function resolveSavePath(): string
    {
        return base_path('storage/sessions');
    }
}
