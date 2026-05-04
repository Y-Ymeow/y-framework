<?php

declare(strict_types=1);

namespace Tests\Support;

class ComponentSnapshot
{
    private string $snapshotDir;

    public function __construct(string $snapshotDir = null)
    {
        $this->snapshotDir = $snapshotDir ?? dirname(__DIR__) . '/__snapshots__';
    }

    public function assertSnapshot(string $name, string $actual): void
    {
        $file = $this->snapshotDir . '/' . $name . '.snap';
        if (!is_dir($this->snapshotDir)) {
            mkdir($this->snapshotDir, 0755, true);
        }
        if (file_exists($file)) {
            $expected = file_get_contents($file);
            if ($expected === $actual) {
                return;
            }
            throw new \PHPUnit\Framework\AssertionFailedError(
                "Snapshot mismatch for '{$name}'.\n" .
                "Expected:\n{$expected}\n\nActual:\n{$actual}\n\n" .
                "To update: rm {$file} && re-run tests."
            );
        }
        file_put_contents($file, $actual);
        throw new \PHPUnit\Framework\AssertionFailedError(
            "Snapshot created for '{$name}'. Re-run to verify. File: {$file}"
        );
    }

    public function getSnapshotPath(string $name): string
    {
        return $this->snapshotDir . '/' . $name . '.snap';
    }

    public function snapshotExists(string $name): bool
    {
        return file_exists($this->getSnapshotPath($name));
    }

    public static function normalizeHtml(string $html): string
    {
        $html = preg_replace('/\s+/', ' ', $html);
        $html = trim($html);
        return $html;
    }
}
