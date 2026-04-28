<?php

declare(strict_types=1);

namespace Framework\Support;

class Finder
{
    private string|array $directory = '';
    private array $patterns = [];
    private array $excludePatterns = [];
    private bool $recursive = false;
    private array $results = [];

    public function in(string|array $directory): self
    {
        $this->directory = $directory;
        return $this;
    }

    public function name(string $pattern): self
    {
        $this->patterns[] = $pattern;
        return $this;
    }

    public function names(array $patterns): self
    {
        $this->patterns = array_merge($this->patterns, $patterns);
        return $this;
    }

    public function exclude(string $pattern): self
    {
        $this->excludePatterns[] = $pattern;
        return $this;
    }

    public function recursive(bool $recursive = true): self
    {
        $this->recursive = $recursive;
        return $this;
    }

    public function getIterator(): array
    {
        $this->results = [];

        $this->scanDirectory($this->directory);
        return array_map(function ($file) {
            return new File($file);
        }, $this->results);
    }

    public function files(): self
    {
        return $this;
    }

    private function scanDirectory(string|array $dir): void
    {
        if (empty($dir)) return;
        $files = [];
        if (is_array($dir)) {
            foreach ($dir as $d) {
                $this->scanDirectory($d);
            }
            return;
        } else {
            $files = array_diff(scandir($dir), ['.', '..']);
        }

        foreach ($files as $file) {
            $path = $dir . '/' . $file;

            if (is_dir($path)) {
                if ($this->shouldExclude($file)) {
                    continue;
                }
                if ($this->recursive) {
                    $this->scanDirectory($path);
                }
                continue;
            }

            if ($this->matchesPatterns($file)) {
                $this->results[] = $path;
            }
        }
    }

    private function shouldExclude(string $filename): bool
    {
        foreach ($this->excludePatterns as $pattern) {
            if (fnmatch($pattern, $filename)) {
                return true;
            }
        }
        return false;
    }

    private function matchesPatterns(string $filename): bool
    {
        if (empty($this->patterns)) {
            return true;
        }

        foreach ($this->patterns as $pattern) {
            if (fnmatch($pattern, $filename)) {
                return true;
            }
        }
        return false;
    }
}

class File
{
    private string $path;
    private ?string $content = null;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function getRealPath(): string
    {
        return realpath($this->path) ?: $this->path;
    }

    public function getContents(): string
    {
        if ($this->content === null) {
            $this->content = file_get_contents($this->path);
        }
        return $this->content;
    }

    public function getFilename(): string
    {
        return basename($this->path);
    }

    public function getExtension(): string
    {
        return pathinfo($this->path, PATHINFO_EXTENSION);
    }

    public function getMTime(): int
    {
        return filemtime($this->path);
    }
}
