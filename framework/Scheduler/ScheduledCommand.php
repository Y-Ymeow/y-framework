<?php

declare(strict_types=1);

namespace Framework\Scheduler;

use Closure;

class ScheduledCommand
{
    private Closure $callback;
    private string $expression = '* * * * *';
    private ?Closure $whenCallback = null;
    private bool $withoutOverlapping = false;
    private int $overlapExpires = 3600;
    private string $output = '/dev/null';
    private ?string $outputFile = null;
    private bool $sendOutputToEmail = false;

    public function __construct(Closure $callback)
    {
        $this->callback = $callback;
    }

    public function cron(string $expression): self
    {
        $this->expression = $expression;
        return $this;
    }

    public function everyMinute(): self
    {
        return $this->cron('* * * * *');
    }

    public function everyFiveMinutes(): self
    {
        return $this->cron('*/5 * * * *');
    }

    public function everyTenMinutes(): self
    {
        return $this->cron('*/10 * * * *');
    }

    public function everyFifteenMinutes(): self
    {
        return $this->cron('*/15 * * * *');
    }

    public function everyThirtyMinutes(): self
    {
        return $this->cron('*/30 * * * *');
    }

    public function hourly(): self
    {
        return $this->cron('0 * * * *');
    }

    public function hourlyAt(int $minute): self
    {
        return $this->cron("{$minute} * * * *");
    }

    public function daily(): self
    {
        return $this->cron('0 0 * * *');
    }

    public function dailyAt(int $hour, int $minute = 0): self
    {
        return $this->cron("{$minute} {$hour} * * *");
    }

    public function weekly(): self
    {
        return $this->cron('0 0 * * 0');
    }

    public function weeklyOn(int $day, int $hour = 0, int $minute = 0): self
    {
        return $this->cron("{$minute} {$hour} * * {$day}");
    }

    public function monthly(): self
    {
        return $this->cron('0 0 1 * *');
    }

    public function monthlyOn(int $day, int $hour = 0, int $minute = 0): self
    {
        return $this->cron("{$minute} {$hour} {$day} * *");
    }

    public function quarterly(): self
    {
        return $this->cron('0 0 1 */3 *');
    }

    public function yearly(): self
    {
        return $this->cron('0 0 1 1 *');
    }

    public function when(Closure $callback): self
    {
        $this->whenCallback = $callback;
        return $this;
    }

    public function withoutOverlapping(int $expires = 3600): self
    {
        $this->withoutOverlapping = true;
        $this->overlapExpires = $expires;
        return $this;
    }

    public function sendOutputTo(string $path): self
    {
        $this->outputFile = $path;
        return $this;
    }

    public function isDue(): bool
    {
        if ($this->whenCallback !== null && !($this->whenCallback)()) {
            return false;
        }

        return $this->expressionMatches();
    }

    private function expressionMatches(): bool
    {
        $parts = explode(' ', $this->expression);
        if (count($parts) !== 5) {
            return false;
        }

        $minute = (int) date('i');
        $hour = (int) date('H');
        $day = (int) date('d');
        $month = (int) date('m');
        $weekday = (int) date('w');

        return $this->matchPart($parts[0], $minute)
            && $this->matchPart($parts[1], $hour)
            && $this->matchPart($parts[2], $day)
            && $this->matchPart($parts[3], $month)
            && $this->matchPart($parts[4], $weekday);
    }

    private function matchPart(string $pattern, int $value): bool
    {
        if ($pattern === '*') return true;

        if (strpos($pattern, '/') !== false) {
            [$base, $step] = explode('/', $pattern);
            $start = $base === '*' ? 0 : (int) $base;
            return ($value - $start) % (int) $step === 0;
        }

        if (strpos($pattern, ',') !== false) {
            return in_array($value, array_map('intval', explode(',', $pattern)), true);
        }

        if (strpos($pattern, '-') !== false) {
            [$start, $end] = explode('-', $pattern);
            return $value >= (int) $start && $value <= (int) $end;
        }

        return (int) $pattern === $value;
    }

    public function run(): void
    {
        if ($this->withoutOverlapping && $this->isAlreadyRunning()) {
            return;
        }

        try {
            $output = '';
            if ($this->outputFile !== null) {
                ob_start();
            }

            ($this->callback)();

            if ($this->outputFile !== null) {
                $output = ob_get_clean();
                file_put_contents($this->outputFile, $output, FILE_APPEND);
            }
        } catch (\Throwable $e) {
            error_log("Scheduled task failed: " . $e->getMessage());
        }
    }

    private function isAlreadyRunning(): bool
    {
        $lockFile = sys_get_temp_dir() . '/scheduler_' . md5(spl_object_id($this->callback)) . '.lock';
        
        if (file_exists($lockFile)) {
            $lockTime = filemtime($lockFile);
            if (time() - $lockTime < $this->overlapExpires) {
                return true;
            }
        }

        touch($lockFile);
        return false;
    }
}
