<?php

declare(strict_types=1);

namespace Admin\DashboardData;

use Admin\Content\Post;
use Admin\Services\AdminManager;
use Framework\Component\Live\LiveComponent;
use Framework\UX\Display\Card;
use Framework\View\Base\Element;

class RecentPostsWidget extends LiveComponent
{
    public static function getOrder(): int { return 20; }
    public function render(): Element
    {
        $card = Card::make()
            ->title(t('admin.stats.recent_posts'));

        try {
            $posts = Post::query()
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
        } catch (\Throwable) {
            $posts = [];
        }

        $list = Element::make('div')->class('divide-y', 'divide-gray-100');

        if (empty($posts)) {
            $list->child(
                Element::make('div')->class('py-3', 'text-sm', 'text-gray-400')
                    ->intl('admin.stats.no_posts', [], '暂无文章')
            );
        } else {
            $prefix = AdminManager::getPrefix() ?: '/admin';

            foreach ($posts as $postData) {
                $item = is_array($postData) ? $postData : $postData->toArray();
                $title = $item['title'] ?? t('admin.stats.untitled');
                $status = $item['status'] ?? 'draft';
                $createdAt = $item['created_at'] ?? '';

                $statusLabels = [
                    'published' => ['label' => t('admin.stats.posts.published'), 'class' => 'text-green-600 bg-green-50'],
                    'draft' => ['label' => t('admin.stats.posts.draft'), 'class' => 'text-yellow-600 bg-yellow-50'],
                    'archived' => ['label' => t('admin.stats.posts.archived'), 'class' => 'text-gray-600 bg-gray-50'],
                ];
                $statusInfo = $statusLabels[$status] ?? $statusLabels['draft'];

                $row = Element::make('div')->class('py-3', 'flex', 'items-center', 'justify-between');

                $left = Element::make('div')->class('flex-1', 'min-w-0');
                $left->child(
                    Element::make('a')
                        ->class('text-sm', 'font-medium', 'text-gray-900', 'truncate', 'block')
                        ->attr('href', "{$prefix}/posts/{$item['id']}/edit")
                        ->attr('data-navigate', '')
                        ->text($title)
                );
                $left->child(
                    Element::make('span')->class('text-xs', 'text-gray-400')->text($createdAt)
                );
                $row->child($left);

                $row->child(
                    Element::make('span')
                        ->class('text-xs', 'px-2', 'py-0.5', 'rounded-full', $statusInfo['class'])
                        ->text($statusInfo['label'])
                );

                $list->child($row);
            }
        }

        $card->child($list);

        return Element::make('div')->class('mb-6')->child($card);
    }
}