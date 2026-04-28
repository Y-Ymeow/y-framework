<?php

declare(strict_types=1);

namespace Framework\Console\Commands;

use Framework\Queue\QueueManager;

class QueueWorkCommand
{
    public function handle(): void
    {
        $this->info("Starting queue worker...");
        $this->info("Press Ctrl+C to stop.");

        while (true) {
            $job = QueueManager::driver()->pop();
            
            if ($job === null) {
                sleep(1);
                continue;
            }

            try {
                $this->info("Processing job: {$job->id} ({$job->jobClass})");
                $job->handle();
                $this->info("Job completed: {$job->id}");
            } catch (\Throwable $e) {
                $this->error("Job failed: {$job->id} - {$e->getMessage()}");
                $job->failed($e);
            }
        }
    }

    private function info(string $message): void
    {
        echo "[" . date('Y-m-d H:i:s') . "] {$message}\n";
    }

    private function error(string $message): void
    {
        echo "[" . date('Y-m-d H:i:s') . "] ERROR: {$message}\n";
    }
}
