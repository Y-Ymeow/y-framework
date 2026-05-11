<?php

declare(strict_types=1);

namespace Admin\Content;

use Framework\Database\Model;

class PostRevision extends Model
{
    protected string $table = 'post_revisions';

    protected array $fillable = [
        'post_id', 'user_id', 'revision_number',
        'title', 'slug', 'excerpt', 'content', 'cover_image', 'status',
        'summary', 'meta_snapshot', 'diff_preview',
    ];

    protected array $casts = [
        'meta_snapshot' => 'json',
        'diff_preview' => 'json',
    ];

    public function post()
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function user()
    {
        return $this->belongsTo(\Admin\Auth\User::class, 'user_id');
    }
}