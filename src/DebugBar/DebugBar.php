<?php

declare(strict_types=1);

namespace Framework\DebugBar;

use Framework\UX\UI\Accordion;
use Framework\View\Document\AssetRegistry;

class DebugBar
{
    private static ?self $instance = null;
    private array $collectors = [];
    private float $startTime;
    private string $key;
    private DebugBarStorage $storage;

    private static array $debugData = [];
    private static array $messages = [];

    public static function debug(mixed ...$data): void
    {
        $debug = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        self::$debugData[] = [
            'file' => $debug[1]['file'],
            'line' => $debug[1]['line'],
            'data' => $data,
        ];
    }

    public static function message(string $message, string $level = 'info'): void
    {
        self::$messages[] = [
            'message' => $message,
            'level' => $level,
            'time' => date('H:i:s'),
        ];
    }

    public static function info(string $message): void
    {
        self::message($message, 'info');
    }
    public static function warning(string $message): void
    {
        self::message($message, 'warning');
    }
    public static function error(string $message): void
    {
        self::message($message, 'error');
    }
    public static function success(string $message): void
    {
        self::message($message, 'success');
    }

    public static function getMessages(): array
    {
        return self::$messages;
    }
    public static function getDebugData(): array
    {
        return self::$debugData;
    }

    public function __construct(?string $key = null)
    {
        $this->startTime = microtime(true);
        $this->storage = DebugBarStorage::make();

        if ($key !== null) {
            $this->key = $key;
        } else {
            // 优先从请求头获取父页面的 Debug ID
            $headerKey = $_SERVER['HTTP_X_DEBUG_KEY'] ?? null;
            if ($headerKey && $headerKey !== '') {
                $this->key = $headerKey;
            } else {
                $this->key = $this->storage->generateKey();
            }
        }

        AssetRegistry::getInstance()->registerScript('debug-bar', DebugBarSource::renderJs());
        Accordion::make();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getKey(): string
    {
        return $this->key;
    }
    public function getStartTime(): float
    {
        return $this->startTime;
    }

    public function addCollector(CollectorInterface $collector): void
    {
        $this->collectors[$collector->getName()] = $collector;
    }

    public function getCollector(string $name): ?CollectorInterface
    {
        return $this->collectors[$name] ?? null;
    }

    /**
     * 执行所有收集器的收集动作并持久化
     */
    public function collect(): void
    {
        foreach ($this->collectors as $collector) {
            $collector->collect();
        }

        $data = [
            'key' => $this->key,
            'summary' => [
                'duration' => number_format((microtime(true) - $this->startTime) * 1000, 2) . 'ms',
                'memory' => $this->formatBytes(memory_get_usage(true)),
                'memory_peak' => $this->formatBytes(memory_get_peak_usage(true)),
            ],
            'php' => [
                'version' => PHP_VERSION,
                'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
                'url' => $_SERVER['REQUEST_URI'] ?? '/',
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
                'time' => date('Y-m-d H:i:s'),
            ],
            'debug' => self::$debugData,
            'messages' => self::$messages,
            'panels' => [],
        ];

        foreach ($this->collectors as $name => $collector) {
            $data['panels'][$name] = [
                'tab' => $collector->getTab(),
                'data' => $collector->getData(),
            ];
        }

        $this->storage->update($this->key, $data);
    }

    public function getSnapshot(): array
    {
        return $this->storage->read($this->key) ?? [];
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024 * 1024) return number_format($bytes / 1024 / 1024 / 1024, 2) . ' GB';
        if ($bytes >= 1024 * 1024) return number_format($bytes / 1024 / 1024, 2) . ' MB';
        return number_format($bytes / 1024, 2) . ' KB';
    }

    /**
     * 兼容旧代码，但不再负责渲染
     */
    public function render(): string
    {
        return '<!-- DebugBar is now a LiveComponent. Use DebugBarComponent to render. -->';
    }
}
