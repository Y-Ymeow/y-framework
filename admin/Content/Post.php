<?php

declare(strict_types=1);

namespace Admin\Content;

use Framework\Database\Model;

class Post extends Model
{
    use HasMeta;

    protected string $table = 'posts';
    protected array $fillable = ['user_id', 'category_id', 'title', 'slug', 'excerpt', 'content', 'cover_image', 'status', 'type', 'published_at'];
    protected array $casts = [
        'published_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'post_tags', 'post_id', 'tag_id');
    }

    public function user()
    {
        return $this->belongsTo(\Admin\Auth\User::class, 'user_id');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'published' => t('admin.published'),
            'draft' => t('admin.draft'),
            'archived' => t('admin.archived'),
            default => $this->status,
        };
    }

    public function syncTags(array $tagIds): void
    {
        db()->table('post_tags')->where('post_id', $this->id)->delete();

        foreach ($tagIds as $tid) {
            db()->table('post_tags')->insert([
                'post_id' => $this->id,
                'tag_id' => (int)$tid,
            ]);
        }
    }

    public function getTagIds(): array
    {
        $results = db()->table('post_tags')
            ->where('post_id', $this->id)
            ->get()
            ->toArray();
        return array_column($results, 'tag_id');
    }
}
