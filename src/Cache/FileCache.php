<?php

declare(strict_types=1);

namespace Framework\Cache;

final class FileCache
{
    public function __construct(
        private string $directory,
    ) {
    }

    public function path(string $name): string
    {
        return rtrim($this->directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;
    }

    public function exists(string $name): bool
    {
        return is_file($this->path($name));
    }

    public function writePhp(string $name, string $contents): void
    {
        $path = $this->path($name);
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        file_put_contents($path, $contents, LOCK_EX);
    }

    public function clear(): void
    {
        $items = glob($this->directory . '/*.php');

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if (is_file($item)) {
                unlink($item);
            }
        }
    }
}
