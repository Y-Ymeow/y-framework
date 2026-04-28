<?php

declare(strict_types=1);

namespace Framework\Queue;

use Framework\Database\Model;

class JobModel extends Model
{
    protected string $table = 'jobs';
    protected array $fillable = ['queue', 'job_class', 'payload', 'attempts', 'max_attempts', 'delay', 'run_at', 'status'];
}

class DatabaseDriver implements QueueDriverInterface
{
    public function push(Job $job): bool
    {
        try {
            $model = new JobModel();
            $model->queue = $job->queue;
            $model->job_class = $job->jobClass;
            $model->payload = serialize($job);
            $model->attempts = $job->attempts;
            $model->max_attempts = $job->maxAttempts;
            $model->delay = $job->delay ?? 0;
            $model->run_at = $job->runAt;
            $model->status = 'pending';
            $model->save();
            return true;
        } catch (\Throwable $e) {
            error_log("Queue push failed: " . $e->getMessage());
            return false;
        }
    }

    public function pop(?string $queue = null): ?Job
    {
        $queue = $queue ?? 'default';
        $now = time();

        $row = JobModel::where('queue', $queue)
            ->where('status', 'pending')
            ->where('run_at', '<=', $now)
            ->orderBy('id', 'ASC')
            ->first();

        if (!$row) {
            return null;
        }

        $job = unserialize($row['payload']);
        JobModel::where('id', $row['id'])->update([
            'status' => 'processing',
            'attempts' => $row['attempts'] + 1,
        ]);

        return $job;
    }

    public function size(?string $queue = null): int
    {
        $queue = $queue ?? 'default';
        return JobModel::where('queue', $queue)->where('status', 'pending')->count();
    }

    public function clear(?string $queue = null): bool
    {
        $queue = $queue ?? 'default';
        JobModel::where('queue', $queue)->where('status', 'pending')->delete();
        return true;
    }
}
