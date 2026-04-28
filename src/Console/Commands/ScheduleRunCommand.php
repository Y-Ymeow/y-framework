<?php

declare(strict_types=1);

namespace Framework\Console\Commands;

use Framework\Scheduler\Scheduler;

class ScheduleRunCommand
{
    public function handle(Scheduler $scheduler): void
    {
        $this->info("Running scheduled tasks...");

        $due = $scheduler->due();
        $count = count($due);

        if ($count === 0) {
            $this->info("No tasks due.");
            return;
        }

        $this->info("{$count} task(s) due.");

        foreach ($due as $event) {
            try {
                $event->run();
                $this->info("Task completed.");
            } catch (\Throwable $e) {
                $this->error("Task failed: " . $e->getMessage());
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
