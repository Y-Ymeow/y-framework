<?php

declare(strict_types=1);

namespace Framework\Http\Upload;

class Upload
{
    private array $file;
    private array $allowedMimes = [];
    private int $maxSize = 0;
    private ?string $destination = null;

    public function __construct(array $file)
    {
        $this->file = $file;
    }

    public static function from(string $key): ?self
    {
        if (!isset($_FILES[$key]) || $_FILES[$key]['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return new self($_FILES[$key]);
    }

    public static function multiple(string $key): array
    {
        if (!isset($_FILES[$key])) return [];

        $files = $_FILES[$key];
        $result = [];

        $count = is_array($files['name']) ? count($files['name']) : 1;

        for ($i = 0; $i < $count; $i++) {
            $file = [
                'name' => is_array($files['name']) ? $files['name'][$i] : $files['name'],
                'type' => is_array($files['type']) ? $files['type'][$i] : $files['type'],
                'tmp_name' => is_array($files['tmp_name']) ? $files['tmp_name'][$i] : $files['tmp_name'],
                'error' => is_array($files['error']) ? $files['error'][$i] : $files['error'],
                'size' => is_array($files['size']) ? $files['size'][$i] : $files['size'],
            ];
            $result[] = new self($file);
        }

        return $result;
    }

    public function isValid(): bool
    {
        return $this->file['error'] === UPLOAD_ERR_OK && is_uploaded_file($this->file['tmp_name']);
    }

    public function getName(): string
    {
        return $this->file['name'];
    }

    public function getExtension(): string
    {
        return strtolower(pathinfo($this->file['name'], PATHINFO_EXTENSION));
    }

    public function getMime(): string
    {
        return $this->file['type'];
    }

    public function getSize(): int
    {
        return $this->file['size'];
    }

    public function getTmpName(): string
    {
        return $this->file['tmp_name'];
    }

    public function getError(): int
    {
        return $this->file['error'];
    }

    public function getErrorMessage(): string
    {
        return match ($this->file['error']) {
            UPLOAD_ERR_OK => '',
            UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize directive',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE directive',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'Upload stopped by extension',
            default => 'Unknown upload error',
        };
    }

    public function allowedMimes(array $mimes): self
    {
        $this->allowedMimes = $mimes;
        return $this;
    }

    public function maxSize(int $bytes): self
    {
        $this->maxSize = $bytes;
        return $this;
    }

    public function to(string $directory): self
    {
        $this->destination = rtrim($directory, '/');
        return $this;
    }

    public function validate(): array
    {
        $errors = [];

        if (!$this->isValid()) {
            $errors[] = $this->getErrorMessage();
            return $errors;
        }

        if (!empty($this->allowedMimes)) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $detected = $finfo->file($this->file['tmp_name']);
            if (!in_array($detected, $this->allowedMimes, true)) {
                $errors[] = 'File type not allowed: ' . $detected;
            }
        }

        if ($this->maxSize > 0 && $this->file['size'] > $this->maxSize) {
            $errors[] = 'File size exceeds limit: ' . $this->formatSize($this->maxSize);
        }

        return $errors;
    }

    public function store(string $directory, ?string $name = null): string
    {
        $dir = rtrim($directory, '/');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $filename = $name ?? $this->generateName();
        $path = $dir . '/' . $filename;

        move_uploaded_file($this->file['tmp_name'], $path);

        return $path;
    }

    public function storeAs(string $directory, string $name): string
    {
        return $this->store($directory, $name);
    }

    public function storePublicly(string $directory, ?string $name = null): string
    {
        $publicDir = 'public/' . trim($directory, '/');
        return $this->store($publicDir, $name);
    }

    private function generateName(): string
    {
        return bin2hex(random_bytes(16)) . '.' . $this->getExtension();
    }

    private function formatSize(int $bytes): string
    {
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . 'MB';
        if ($bytes >= 1024) return round($bytes / 1024, 1) . 'KB';
        return $bytes . 'B';
    }
}
