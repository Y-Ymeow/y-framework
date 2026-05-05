<?php

declare(strict_types=1);

namespace Framework\Scheduler;

use Framework\Scheduler\ScheduledCommand;

class Scheduler
{
    private array $events = [];
    private string $output = '/dev/null';
    private ?string $email = null;

    public function call(callable $callback): ScheduledCommand
    {
        $event = new ScheduledCommand($callback);
        $this->events[] = $event;
        return $event;
    }

    public function command(string $command): ScheduledCommand
    {
        $event = new ScheduledCommand(fn() => app('console.kernel')->call($command));
        $this->events[] = $event;
        return $event;
    }

    public function getEvents(): array
    {
        return $this->events;
    }

    public function due(): array
    {
        return array_filter($this->events, fn($event) => $event->isDue());
    }

    public function run(): void
    {
        foreach ($this->due() as $event) {
            $event->run();
        }
    }

    public function emailOutputTo(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function output(string $output): self
    {
        $this->output = $output;
        return $this;
    }

    public function getOutput(): string
    {
        return $this->output;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }
}
