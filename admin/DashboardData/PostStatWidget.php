<?php

declare(strict_types=1);

namespace Admin\DashboardData;

use Admin\Content\Post;
use Framework\Component\Live\LiveComponent;
use Framework\UX\Display\StatCard;
use Framework\View\Base\Element;

class PostStatWidget extends LiveComponent
{
    public static function getOrder(): int { return 10; }
    public static function getSection(): string { return 'stats'; }

    public function render(): Element
    {
        try {
            $total = Post::query()->count();
            $published = Post::query()->where('status', 'published')->count();
            $draft = Post::query()->where('status', 'draft')->count();
            $archived = Post::query()->where('status', 'archived')->count();
        } catch (\Throwable) {
            $total = $published = $draft = $archived = 0;
        }

        $wrapper = Element::make('div')->class('grid', 'grid-cols-4', 'gap-4');

        $wrapper->child(
            StatCard::make()
                ->value((string)$total)
                ->variant('primary')
        );

        $wrapper->child(
            StatCard::make()
                ->title(t('admin.stats.posts.published'))
                ->value((string)$published)
                ->variant('success')
        );

        $wrapper->child(
            StatCard::make()
                ->title(t('admin.stats.posts.draft'))
                ->value((string)$draft)
                ->variant('warning')
        );

        $wrapper->child(
            StatCard::make()
                ->title(t('admin.stats.posts.archived'))
                ->value((string)$archived)
                ->variant('secondary')
        );

        return Element::make('div')->children(
            $wrapper
        );
    }
}