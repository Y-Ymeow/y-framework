<?php

declare(strict_types=1);

namespace Framework\DebugBar;

class DebugBarStorage
{
    private string $path;
    private int $lifetime;
    private static array $payload = [];

    public function __construct(?string $path = null, int $lifetime = 3600)
    {
        $this->path = $path ?? base_path('storage/debug');
        $this->lifetime = $lifetime;

        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
    }

    public function generateKey(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function save(string $key, array $data): void
    {
        $file = $this->getFilePath($key);
        self::$payload = [
            'data' => $data,
            'expires' => time() + $this->lifetime,
        ];
    }

    public function read(string $key): ?array
    {
        // $file = $this->getFilePath($key);

        // if (!file_exists($file)) {
        //     return null;
        // }

        // $content = file_get_contents($file);
        // if ($content === false) {
        //     return null;
        // }

        // $payload = json_decode($content, true);
        // if (!$payload || !isset($payload['expires'])) {
        //     return null;
        // }

        // if ($payload['expires'] < time()) {
        //     unlink($file);
        //     return null;
        // }
        
        return self::$payload['data'] ?? null;
    }

    public function append(string $key, string $field, mixed $value): void
    {
        $data = $this->read($key) ?? [];

        if (!isset($data[$field]) || !is_array($data[$field])) {
            $data[$field] = [];
        }

        $data[$field][] = $value;

        if (count($data[$field]) > 50) {
            $data[$field] = array_slice($data[$field], -50);
        }

        $this->save($key, $data);
    }

    public function update(string $key, array $data): void
    {
        $existing = $this->read($key) ?? [];
        $data = array_merge($existing, $data);
        $this->save($key, $data);
    }

    public function exists(string $key): bool
    {
        return file_exists($this->getFilePath($key));
    }

    public function delete(string $key): void
    {
        $file = $this->getFilePath($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    public function clear(): void
    {
        $files = glob($this->path . '/*.json');
        if ($files) {
            foreach ($files as $file) {
                unlink($file);
            }
        }
    }

    public function prune(): int
    {
        $count = 0;
        $files = glob($this->path . '/*.json');

        if ($files) {
            foreach ($files as $file) {
                $content = file_get_contents($file);
                if ($content === false) {
                    continue;
                }

                $payload = json_decode($content, true);
                if ($payload && isset($payload['expires']) && $payload['expires'] < time()) {
                    unlink($file);
                    $count++;
                }
            }
        }

        return $count;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    private function getFilePath(string $key): string
    {
        return $this->path . '/' . $key . '.json';
    }

    public static function make(): self
    {
        static $instance = null;

        if ($instance === null) {
            $instance = new self();
        }

        return $instance;
    }
}
