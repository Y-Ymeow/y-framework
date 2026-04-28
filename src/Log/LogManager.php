<?php

declare(strict_types=1);

namespace Framework\Log;

use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger as MonologLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

class LogManager implements LoggerInterface
{
    use LoggerTrait;

    private MonologLogger $logger;

    public function __construct(array $config = [])
    {
        $name = $config['name'] ?? 'app';
        $this->logger = new MonologLogger($name);

        $default = $config['default'] ?? 'single';
        $channelConfig = $config['channels'][$default] ?? [];
        
        $level = $channelConfig['level'] ?? 'debug';
        $logPath = $channelConfig['path'] ?? dirname(__DIR__, 2) . '/storage/logs/app.log';
        
        $dir = dirname($logPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        if (($channelConfig['driver'] ?? 'single') === 'daily') {
            $this->logger->pushHandler(new \Monolog\Handler\RotatingFileHandler($logPath, $channelConfig['days'] ?? 14, $level));
        } else {
            $this->logger->pushHandler(new StreamHandler($logPath, $level));
        }
    }

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
    }

    public function getLogger(): MonologLogger
    {
        return $this->logger;
    }
}
