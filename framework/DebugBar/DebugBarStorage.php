<?php

declare(strict_types=1);

namespace Framework\DebugBar;

class DebugBarStorage
{
    private string $path;
    private int $lifetime;

    public function __construct(?string $path = null, int $lifetime = 3600)
    {
        $this->path = $path ?? paths()->debug();
        $this->lifetime = $lifetime;

        if (!is_dir($this->path)) {
            @mkdir($this->path, 0755, true);
        }
    }

    public function generateKey(): string
    {
        return bin2hex(random_bytes(16));
    }

    public function save(string $key, array $data): void
    {
        $file = $this->getFilePath($key);
        $payload = [
            'data' => $data,
            'expires' => time() + $this->lifetime,
        ];
        
        // 使用文件锁防止并发写入冲突
        file_put_contents($file, json_encode($payload, JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    public function read(string $key): ?array
    {
        $file = $this->getFilePath($key);

        if (!file_exists($file)) {
            return null;
        }

        $content = file_get_contents($file);
        if ($content === false) {
            return null;
        }

        $payload = json_decode($content, true);
        if (!$payload || !isset($payload['expires'])) {
            return null;
        }

        if ($payload['expires'] < time()) {
            @unlink($file);
            return null;
        }
        
        return $payload['data'] ?? null;
    }

    /**
     * 智能合并：追加新快照中的数据
     */
    public function update(string $key, array $data): void
    {
        $existing = $this->read($key) ?? [];
        
        // 合并规则：
        // 1. summary 和 php 等汇总信息，以最新的为准（或保留主请求的，这里我们简单合并）
        // 2. panels 下的数据需要深度合并，特别是 history, queries, debug, messages 等列表
        
        foreach ($data as $field => $value) {
            if ($field === 'panels') {
                foreach ($value as $panelName => $panelData) {
                    if (!isset($existing['panels'][$panelName])) {
                        $existing['panels'][$panelName] = $panelData;
                        continue;
                    }

                    // 特殊处理 Requests Panel 的 history
                    if ($panelName === 'request' && isset($panelData['data']['history'])) {
                        $existing['panels'][$panelName]['data']['history'] = array_merge(
                            $existing['panels'][$panelName]['data']['history'] ?? [],
                            $panelData['data']['history']
                        );
                        // 去重
                        $existing['panels'][$panelName]['data']['history'] = $this->uniqueById($existing['panels'][$panelName]['data']['history']);
                        $existing['panels'][$panelName]['data']['total'] = count($existing['panels'][$panelName]['data']['history']);
                    } 
                    // 特殊处理 SQL Panel 的 queries
                    elseif ($panelName === 'sql' && isset($panelData['data']['queries'])) {
                        $existing['panels'][$panelName]['data']['queries'] = array_merge(
                            $existing['panels'][$panelName]['data']['queries'] ?? [],
                            $panelData['data']['queries']
                        );
                        $existing['panels'][$panelName]['data']['total_queries'] = count($existing['panels'][$panelName]['data']['queries']);
                    }
                    else {
                        $existing['panels'][$panelName] = $panelData;
                    }
                }
            } elseif (in_array($field, ['debug', 'messages'])) {
                $existing[$field] = array_merge($existing[$field] ?? [], $value);
            } else {
                $existing[$field] = $value;
            }
        }

        $this->save($key, $existing);
    }

    private function uniqueById(array $items): array
    {
        $seen = [];
        $unique = [];
        foreach ($items as $item) {
            $id = $item['id'] ?? md5(serialize($item));
            if (!isset($seen[$id])) {
                $seen[$id] = true;
                $unique[] = $item;
            }
        }
        return $unique;
    }

    public function exists(string $key): bool
    {
        return file_exists($this->getFilePath($key));
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
