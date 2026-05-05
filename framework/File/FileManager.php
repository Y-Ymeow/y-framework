<?php

declare(strict_types=1);

namespace Framework\File;

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\Visibility;

class FileManager
{
    private Filesystem $fs;
    private string $root;

    public function __construct(string $root)
    {
        $this->root = $root;
        $adapter = new LocalFilesystemAdapter($root);
        $this->fs = new Filesystem($adapter);
    }

    public function read(string $path): string
    {
        return $this->fs->read($path);
    }

    public function write(string $path, string $contents, array $config = []): void
    {
        $config['visibility'] = $config['visibility'] ?? Visibility::PUBLIC;
        $this->fs->write($path, $contents, $config);
    }

    public function exists(string $path): bool
    {
        return $this->fs->fileExists($path);
    }

    public function delete(string $path): void
    {
        $this->fs->delete($path);
    }

    public function move(string $from, string $to): void
    {
        $this->fs->move($from, $to);
    }

    public function copy(string $from, string $to): void
    {
        $this->fs->copy($from, $to);
    }

    public function mkdir(string $path, array $config = []): void
    {
        $this->fs->createDirectory($path, $config);
    }

    public function rmdir(string $path): void
    {
        $this->fs->deleteDirectory($path);
    }

    public function size(string $path): int
    {
        return $this->fs->fileSize($path);
    }

    public function mimeType(string $path): string
    {
        return $this->fs->mimeType($path);
    }

    public function lastModified(string $path): int
    {
        return $this->fs->lastModified($path);
    }

    public function listContents(string $path = '', bool $recursive = false): array
    {
        return $this->fs->listContents($path, $recursive)->toArray();
    }

    public function url(string $path): string
    {
        return '/' . ltrim($path, '/');
    }

    public function getFs(): Filesystem
    {
        return $this->fs;
    }
}
