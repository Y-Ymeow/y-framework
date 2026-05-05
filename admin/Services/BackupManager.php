<?php

declare(strict_types=1);

namespace Admin\Services;

class BackupManager
{
    protected string $backupPath;

    public function __construct()
    {
        $this->backupPath = base_path('/storage/backups');
    }

    public function getBackupPath(): string
    {
        return $this->backupPath;
    }

    public function listBackups(): array
    {
        if (!is_dir($this->backupPath)) {
            return [];
        }

        $backups = [];
        foreach (glob($this->backupPath . '/*.sql') as $file) {
            $backups[] = $this->getFileInfo($file);
        }
        foreach (glob($this->backupPath . '/*.zip') as $file) {
            $backups[] = $this->getFileInfo($file);
        }

        usort($backups, fn($a, $b) => strtotime($b['modified']) - strtotime($a['modified']));

        return $backups;
    }

    public function createDatabaseBackup(): array
    {
        if (!is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }

        $filename = 'db_' . date('Y-m-d_His') . '.sql';
        $filepath = $this->backupPath . '/' . $filename;

        $connection = db();
        $tables = $connection->select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");

        $sql = "-- Database Backup\n-- Generated at " . date('Y-m-d H:i:s') . "\n\n";

        foreach ($tables as $table) {
            $tableName = is_array($table) ? $table['name'] : $table->name;
            $rows = $connection->table($tableName)->get();

            foreach ($rows as $row) {
                $row = (array)$row;
                $columns = array_keys($row);
                $values = array_map(function ($v) {
                    return is_null($v) ? 'NULL' : "'" . addslashes((string)$v) . "'";
                }, array_values($row));

                $sql .= "INSERT INTO `{$tableName}` (`" . implode('`, `', $columns) . '`) VALUES (' . implode(', ', $values) . ");\n";
            }
            $sql .= "\n";
        }

        file_put_contents($filepath, $sql);

        return [
            'success' => true,
            'file' => $filepath,
            'size' => filesize($filepath),
        ];
    }

    public function deleteBackup(string $filename): array
    {
        $filepath = $this->backupPath . '/' . basename($filename);

        if (!file_exists($filepath)) {
            return ['success' => false, 'error' => '备份文件不存在'];
        }

        if (!str_starts_with(realpath($filepath), realpath($this->backupPath))) {
            return ['success' => false, 'error' => '非法路径'];
        }

        unlink($filepath);

        return ['success' => true];
    }

    public function getBackupContent(string $filename): ?string
    {
        $filepath = $this->backupPath . '/' . basename($filename);

        if (!file_exists($filepath)) {
            return null;
        }

        if (!str_starts_with(realpath($filepath), realpath($this->backupPath))) {
            return null;
        }

        return file_get_contents($filepath);
    }

    protected function getFileInfo(string $filepath): array
    {
        return [
            'name' => basename($filepath),
            'path' => $filepath,
            'size' => $this->formatSize(filesize($filepath)),
            'type' => pathinfo($filepath, PATHINFO_EXTENSION),
            'modified' => date('Y-m-d H:i:s', filemtime($filepath)),
        ];
    }

    protected function formatSize(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
