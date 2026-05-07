<?php

declare(strict_types=1);

namespace Framework\Log;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use Stringable;

class LogManager implements LoggerInterface
{
    use LoggerTrait;

    private array $config;
    private array $channels = [];
    private array $levelMap = [
        LogLevel::EMERGENCY => 7,
        LogLevel::ALERT     => 6,
        LogLevel::CRITICAL  => 5,
        LogLevel::ERROR     => 4,
        LogLevel::WARNING   => 3,
        LogLevel::NOTICE    => 2,
        LogLevel::INFO      => 1,
        LogLevel::DEBUG     => 0,
    ];

    private static array $defaults = [
        'default' => 'single',
        'channels' => [
            'single' => [
                'driver' => 'single',
                'path' => null,
                'level' => 'debug',
            ],
            'daily' => [
                'driver' => 'daily',
                'path' => null,
                'level' => 'debug',
            ],
            'stderr' => [
                'driver' => 'stderr',
                'level' => 'debug',
            ],
        ],
    ];

    public function __construct(array $config = [])
    {
        $this->config = array_merge(self::$defaults, $config);
    }

    public function channel(?string $name = null): LoggerInterface
    {
        $channel = $name ?? $this->config['default'] ?? 'single';
        if (!isset($this->channels[$channel])) {
            $this->channels[$channel] = $this->resolveChannel($channel);
        }
        return $this->channels[$channel];
    }

    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->channel()->log($level, $message, $context);
    }

    public function emergency(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug(string|Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    private function resolveChannel(string $name): LoggerInterface
    {
        $channelConfig = $this->config['channels'][$name] ?? $this->config['channels']['single'] ?? self::$defaults['channels']['single'];

        return new class($channelConfig, $this->levelMap) implements LoggerInterface {
            use LoggerTrait;

            private array $config;
            private array $levelMap;

            public function __construct(array $config, array $levelMap)
            {
                $this->config = $config;
                $this->levelMap = $levelMap;
            }

            public function log($level, string|Stringable $message, array $context = []): void
            {
                $minLevel = $this->config['level'] ?? 'debug';
                if (($this->levelMap[$level] ?? 0) < ($this->levelMap[$minLevel] ?? 0)) {
                    return;
                }

                $formatted = $this->format($level, (string) $message, $context);
                $driver = $this->config['driver'] ?? 'single';

                match ($driver) {
                    'daily' => $this->writeToDaily($formatted),
                    'stderr' => $this->writeToStderr($formatted),
                    default => $this->writeToSingle($formatted),
                };
            }

            private function format(string $level, string $message, array $context): string
            {
                $time = date('Y-m-d H:i:s');
                $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
                return sprintf("[%s] %s: %s%s%s", $time, strtoupper($level), $message, $contextStr, PHP_EOL);
            }

            private function logPath(): string
            {
                if (!empty($this->config['path'])) {
                    return $this->config['path'];
                }
                try {
                    return paths()->logs('app.log');
                } catch (\Throwable) {
                    return sys_get_temp_dir() . '/framework.log';
                }
            }

            private function writeToSingle(string $content): void
            {
                $path = $this->logPath();
                $this->ensureDirectoryExists(dirname($path));
                file_put_contents($path, $content, FILE_APPEND | LOCK_EX);
            }

            private function writeToDaily(string $content): void
            {
                $path = $this->logPath();
                $info = pathinfo($path);
                $datedPath = $info['dirname'] . '/' . $info['filename'] . '-' . date('Y-m-d') . (isset($info['extension']) ? '.' . $info['extension'] : '');
                $this->ensureDirectoryExists(dirname($datedPath));
                file_put_contents($datedPath, $content, FILE_APPEND | LOCK_EX);
            }

            private function writeToStderr(string $content): void
            {
                file_put_contents('php://stderr', $content);
            }

            private function ensureDirectoryExists(string $dir): void
            {
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
            }
        };
    }

    public static function gc(string $logDir, int $maxAge = 14): int
    {
        if (!is_dir($logDir)) {
            return 0;
        }

        $now = time();
        $maxAgeSeconds = $maxAge * 86400;
        $count = 0;

        foreach (glob($logDir . '/*') as $file) {
            if (is_file($file) && ($now - filemtime($file) > $maxAgeSeconds)) {
                @unlink($file);
                $count++;
            }
        }

        return $count;
    }
}
