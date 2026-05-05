<?php

declare(strict_types=1);

namespace App\Jobs;

class DemoJob
{
    public function handle(array $data = []): void
    {
        $message = $data['message'] ?? 'no message';
        $logFile = base_path('storage/logs/queue.log');
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($logFile, "[{$timestamp}] DemoJob executed: {$message}\n", FILE_APPEND);
    }

    public function failed(array $data = [], ?\Throwable $e = null): void
    {
        $logFile = base_path('storage/logs/queue.log');
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($logFile, "[{$timestamp}] DemoJob FAILED: " . ($e?->getMessage() ?? 'unknown') . "\n", FILE_APPEND);
    }
}
