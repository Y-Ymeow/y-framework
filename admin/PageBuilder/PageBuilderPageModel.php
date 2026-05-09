<?php

declare(strict_types=1);

namespace Admin\PageBuilder;

use Framework\Database\Model;

class PageBuilderPageModel extends Model
{
    protected string $table = 'page_builder_pages';

    protected array $fillable = ['name', 'slug', 'route', 'middleware', 'component_tree'];

    protected array $casts = [
        'middleware' => 'json',
        'component_tree' => 'json',
    ];
}
