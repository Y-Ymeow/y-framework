<?php

declare(strict_types=1);

namespace Admin\PageBuilder;

use Framework\Database\Model;

class PageBuilderPageModel extends Model
{
    protected string $table = 'page_builder_pages';

    protected array $fillable = ['name', 'route', 'component_tree'];

    protected array $casts = [
        'component_tree' => 'json',
    ];
}
