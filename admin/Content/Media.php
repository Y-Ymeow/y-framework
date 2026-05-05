<?php

declare(strict_types=1);

namespace Admin\Content;

use Framework\Database\Model;

class Media extends Model
{
    protected string $table = 'media';
    protected array $fillable = ['user_id', 'disk', 'path', 'filename', 'extension', 'mime_type', 'size', 'alt', 'title', 'metadata'];
    protected array $casts = [
        'metadata' => 'json',
        'size' => 'int',
    ];

    public static function boot(): void
    {
        static::creating(function (self $media) {
            if (empty($media->user_id) && auth()->check()) {
                $media->user_id = auth()->id();
            }
        });
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function isVideo(): bool
    {
        return str_starts_with($this->mime_type, 'video/');
    }

    public function isDocument(): bool
    {
        return in_array($this->extension, ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'], true);
    }

    public function getSizeFormatted(): string
    {
        $bytes = (int)$this->size;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }

    public function getUrl(): string
    {
        if ($this->disk === 'public') {
            return '/storage/' . $this->path;
        }
        return '/media/' . $this->id;
    }

    public function getThumbnailUrl(): string
    {
        if ($this->isImage()) {
            return $this->getUrl() . '?w=150&h=150&fit=true';
        }
        return '';
    }
}
