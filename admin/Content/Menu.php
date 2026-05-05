<?php

declare(strict_types=1);

namespace Admin\Content;

use Framework\Database\Model;

class Menu extends Model
{
    protected string $table = 'menus';
    protected array $fillable = ['name', 'slug'];

    public function items()
    {
        return $this->hasMany(MenuItem::class, 'menu_id');
    }

    public static function getSidebarMenu(): ?self
    {
        $result = static::where('slug', 'admin_sidebar')->first();
        if (!$result) {
            return null;
        }
        $data = $result->toArray();
        return static::find($data['id']);
    }

    public function getItemsTree(): array
    {
        $allItems = db()->table('menu_items')
            ->where('menu_id', $this->id)
            ->where('is_active', true)
            ->orderBy('sort', 'asc')
            ->get();

        $items = [];
        foreach ($allItems as $item) {
            $items[] = $item->toArray();
        }

        return $this->buildTree($items);
    }

    public function getItemsTreeForUser(array $userPermissions): array
    {
        $allItems = db()->table('menu_items')
            ->where('menu_id', $this->id)
            ->where('is_active', true)
            ->orderBy('sort', 'asc')
            ->get();

        $isSuperAdmin = in_array('*', $userPermissions, true);

        $items = [];
        foreach ($allItems as $item) {
            $row = $item->toArray();
            if (!$isSuperAdmin && !empty($row['permission']) && !in_array($row['permission'], $userPermissions, true)) {
                continue;
            }
            $items[] = $row;
        }

        return $this->buildTree($items);
    }

    protected function buildTree(array $items, ?int $parentId = null): array
    {
        $tree = [];
        foreach ($items as $item) {
            $itemParentId = $item['parent_id'] ?? null;
            $matches = ($parentId === null && ($itemParentId === null || $itemParentId === ''))
                || (string)$itemParentId === (string)$parentId;
            if ($matches) {
                $children = $this->buildTree($items, (int)$item['id']);
                $item['children'] = $children;
                $tree[] = $item;
            }
        }
        return $tree;
    }
}
