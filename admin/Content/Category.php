<?php

declare(strict_types=1);

namespace Admin\Content;

use Framework\Database\Model;

class Category extends Model
{
    use HasMeta;

    protected string $table = 'categories';
    protected array $fillable = ['parent_id', 'name', 'slug', 'description', 'sort'];

    public function posts()
    {
        return $this->hasMany(Post::class, 'category_id');
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public static function getTree(): array
    {
        $all = static::query()->orderBy('sort', 'asc')->get()->toArray();
        return static::buildTree($all);
    }

    protected static function buildTree(array $items, ?int $parentId = null): array
    {
        $tree = [];
        foreach ($items as $item) {
            $itemParentId = $item['parent_id'] ?? null;
            if (($parentId === null && empty($itemParentId)) || (string)$itemParentId === (string)$parentId) {
                $item['children'] = static::buildTree($items, (int)$item['id']);
                $tree[] = $item;
            }
        }
        return $tree;
    }
}
