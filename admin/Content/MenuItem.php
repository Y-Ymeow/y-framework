<?php

declare(strict_types=1);

namespace Admin\Content;

use Framework\Database\Model;

class MenuItem extends Model
{
    protected string $table = 'menu_items';
    protected array $fillable = ['menu_id', 'parent_id', 'title', 'url', 'icon', 'target', 'permission', 'sort', 'is_active'];
    protected array $casts = [
        'is_active' => 'bool',
    ];

    public function menu()
    {
        return $this->belongsTo(Menu::class, 'menu_id');
    }

    public function children()
    {
        return $this->hasMany(MenuItem::class, 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }
}
