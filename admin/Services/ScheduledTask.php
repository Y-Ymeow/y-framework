<?php

declare(strict_types=1);

namespace Admin\Services;

use Framework\Database\Model;

class ScheduledTask extends Model
{
    protected string $table = 'scheduled_tasks';
    protected array $fillable = ['name', 'command', 'expression', 'is_active', 'description', 'last_run_at', 'last_status', 'next_run_at'];
    protected array $casts = [
        'is_active' => 'bool',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
    ];

    public function logs()
    {
        return $this->hasMany(ScheduledTaskLog::class, 'task_id');
    }
}
