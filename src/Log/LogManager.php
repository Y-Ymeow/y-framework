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

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * @param mixed $level
     * @param string|Stringable $message
     * @param array $context
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $default = $this->config['default'] ?? 'single';
        $channelConfig = $this->config['channels'][$default] ?? [];
        
        $minLevel = $channelConfig['level'] ?? 'debug';
        if (($this->levelMap[$level] ?? 0) < ($this->levelMap[$minLevel] ?? 0)) {
            return;
        }

        $formatted = $this->format($level, (string) $message, $context);
        $driver = $channelConfig['driver'] ?? 'single';

        switch ($driver) {
            case 'daily':
                $this->writeToDaily($channelConfig['path'] ?? base_path('storage/logs/app.log'), $formatted);
                break;
            case 'stderr':
                $this->writeToStderr($formatted);
                break;
            case 'single':
            default:
                $this->writeToSingle($channelConfig['path'] ?? base_path('storage/logs/app.log'), $formatted);
                break;
        }
    }

    private function format(string $level, string $message, array $context): string
    {
        $time = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
        
        return sprintf("[%s] %s: %s%s%s", $time, strtoupper($level), $message, $contextStr, PHP_EOL);
    }

    private function writeToSingle(string $path, string $content): void
    {
        $this->ensureDirectoryExists(dirname($path));
        file_put_contents($path, $content, FILE_APPEND | LOCK_EX);
    }

    private function writeToDaily(string $path, string $content): void
    {
        $info = pathinfo($path);
        $datedPath = $info['dirname'] . '/' . $info['filename'] . '-' . date('Y-m-d') . (isset($info['extension']) ? '.' . $info['extension'] : '');
        
        $this->writeToSingle($datedPath, $content);
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

    /**
     * 清理过期日志文件
     *
     * @param string $logDir 日志目录
     * @param int $maxAge 保留天数
     * @return int 清理的文件数
     */
    public static function gc(string $logDir, int $maxAge = 14): int
    {
        if (!is_dir($logDir)) {
            return 0;
        }

        $now = time();
        $maxAgeSeconds = $maxAge * 86400;
        $count = 0;

        $files = glob($logDir . '/*');
        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file) > $maxAgeSeconds)) {
                @unlink($file);
                $count++;
            }
        }

        return $count;
    }
}
