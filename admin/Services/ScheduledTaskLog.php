<?php

declare(strict_types=1);

namespace Admin\Services;

use Framework\Database\Model;

class ScheduledTaskLog extends Model
{
    protected string $table = 'scheduled_task_logs';
    protected array $fillable = ['task_id', 'started_at', 'finished_at', 'status', 'output', 'error'];
    protected array $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function task()
    {
        return $this->belongsTo(ScheduledTask::class, 'task_id');
    }
}
