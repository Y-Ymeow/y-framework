<?php

declare(strict_types=1);

namespace Framework\Queue;

class Job
{
    public string $id;
    public string $jobClass;
    public array $data = [];
    public string $queue = 'default';
    public int $attempts = 0;
    public int $maxAttempts = 3;
    public ?int $delay = 0;
    public ?int $runAt = null;
    public string $status = 'pending';
    public ?string $error = null;
    public int $createdAt;

    public function __construct()
    {
        $this->id = uniqid('job_', true);
        $this->createdAt = time();
        $this->runAt = time();
    }

    public static function make(string $job, array $data = []): self
    {
        $instance = new self();
        $instance->jobClass = $job;
        $instance->data = $data;
        return $instance;
    }

    public function setQueue(string $queue): self
    {
        $this->queue = $queue;
        return $this;
    }

    public function setDelay(int $seconds): self
    {
        $this->delay = $seconds;
        $this->runAt = time() + $seconds;
        return $this;
    }

    public function setMaxAttempts(int $attempts): self
    {
        $this->maxAttempts = $attempts;
        return $this;
    }

    public function handle(): void
    {
        if (!class_exists($this->jobClass)) {
            throw new \RuntimeException("Job class not found: {$this->jobClass}");
        }

        $instance = new $this->jobClass();
        
        if (!method_exists($instance, 'handle')) {
            throw new \RuntimeException("Job class {$this->jobClass} does not have a handle method");
        }

        $instance->handle($this->data);
    }

    public function failed(\Throwable $e): void
    {
        $this->status = 'failed';
        $this->error = $e->getMessage();

        if (class_exists($this->jobClass)) {
            $instance = new $this->jobClass();
            if (method_exists($instance, 'failed')) {
                $instance->failed($this->data, $e);
            }
        }
    }

    public function release(int $delay = 0): void
    {
        $this->attempts++;
        $this->status = 'pending';
        $this->runAt = time() + $delay;
    }

    public function delete(): void
    {
        $this->status = 'deleted';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'job_class' => $this->jobClass,
            'data' => $this->data,
            'queue' => $this->queue,
            'attempts' => $this->attempts,
            'max_attempts' => $this->maxAttempts,
            'delay' => $this->delay,
            'run_at' => $this->runAt,
            'status' => $this->status,
            'error' => $this->error,
            'created_at' => $this->createdAt,
        ];
    }

    public static function fromArray(array $data): self
    {
        $job = new self();
        $job->id = $data['id'] ?? $job->id;
        $job->jobClass = $data['job_class'] ?? '';
        $job->data = $data['data'] ?? [];
        $job->queue = $data['queue'] ?? 'default';
        $job->attempts = $data['attempts'] ?? 0;
        $job->maxAttempts = $data['max_attempts'] ?? 3;
        $job->delay = $data['delay'] ?? 0;
        $job->runAt = $data['run_at'] ?? time();
        $job->status = $data['status'] ?? 'pending';
        $job->error = $data['error'] ?? null;
        $job->createdAt = $data['created_at'] ?? time();
        return $job;
    }
}
