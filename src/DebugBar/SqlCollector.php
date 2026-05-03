<?php

declare(strict_types=1);

namespace Framework\DebugBar;

use Framework\Foundation\Application;
use Framework\Database\Connection;
use Framework\Database\Model;

class SqlCollector implements CollectorInterface
{
    private array $queries = [];
    private string $totalTime = '0ms';

    public function getName(): string
    {
        return 'sql';
    }

    public function getTab(): array
    {
        $count = count($this->queries);
        return [
            'label' => 'SQL',
            'icon' => '🗃️',
            'badge' => $count > 0 ? (string)$count : null,
        ];
    }

    public function getData(): array
    {
        return [
            'total_queries' => count($this->queries),
            'total_time' => $this->totalTime,
            'queries' => $this->queries,
        ];
    }

    public function collect(): void
    {
        $connection = null;
        try {
            $connection = Connection::get();
        } catch (\Throwable $e) {}

        if (!$connection) {
            try {
                $connection = Model::getConnection();
            } catch (\Throwable $e) {}
        }

        if (!$connection) {
            return;
        }

        $rawQueries = $connection->getQueries();
        $this->totalTime = $connection->getTotalQueryTime();
        
        $this->queries = array_map(function($q) {
            return [
                'sql' => $q['sql'],
                'bindings' => $q['bindings'],
                'time' => $q['time'],
            ];
        }, $rawQueries);
    }

    /**
     * 保持静态方法以兼容旧调用
     */
    public static function register(): void
    {
        $debugBar = DebugBar::getInstance();
        if (!$debugBar->getCollector('sql')) {
            $debugBar->addCollector(new self());
        }
    }
}
