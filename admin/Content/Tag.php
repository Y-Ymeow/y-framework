<?php

declare(strict_types=1);

namespace Admin\Content;

use Framework\Database\Model;

class Tag extends Model
{
    protected string $table = 'tags';
    protected array $fillable = ['name', 'slug'];

    public function posts()
    {
        return $this->belongsToMany(Post::class, 'post_tags', 'tag_id', 'post_id');
    }
}
