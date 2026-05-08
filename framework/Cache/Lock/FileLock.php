<?php

declare(strict_types=1);

namespace Framework\Cache\Lock;

class FileLock extends Lock
{
    private ?string $lockPath = null;

    public function __construct(
        string $key,
        int $seconds = 0,
        private readonly string $directory = '',
    ) {
        parent::__construct($key, $seconds);
        $this->lockPath = $this->directory . '/' . md5($this->key) . '.lock';
    }

    public function acquire(): bool
    {
        $dir = dirname($this->lockPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $handle = @fopen($this->lockPath, 'c+');
        if ($handle === false) {
            return false;
        }

        if (!flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            return false;
        }

        ftruncate($handle, 0);
        fwrite($handle, $this->owner);
        fflush($handle);

        if ($this->seconds > 0) {
            $expires = time() + $this->seconds;
            $metaPath = $this->lockPath . '.meta';
            file_put_contents($metaPath, (string) $expires);
        }

        return true;
    }

    public function release(): bool
    {
        if (!$this->isOwnedByCurrentProcess()) {
            return false;
        }

        $handle = @fopen($this->lockPath, 'c+');
        if ($handle === false) {
            return false;
        }

        flock($handle, LOCK_UN);
        fclose($handle);

        @unlink($this->lockPath);
        @unlink($this->lockPath . '.meta');

        return true;
    }

    public function isOwnedByCurrentProcess(): bool
    {
        if (!is_file($this->lockPath)) {
            return false;
        }

        $metaPath = $this->lockPath . '.meta';
        if (is_file($metaPath)) {
            $expires = (int) file_get_contents($metaPath);
            if ($expires < time()) {
                return false;
            }
        }

        $content = @file_get_contents($this->lockPath);
        return $content === $this->owner;
    }
}
