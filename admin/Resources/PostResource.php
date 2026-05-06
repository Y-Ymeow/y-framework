<?php

namespace Admin\Resources;

use Admin\Contracts\Resource\AdminResource;
use Admin\Contracts\Resource\BaseResource;
use Admin\Content\Post;
use Framework\UX\Form\FormBuilder;
use Framework\UX\Data\DataTable;
use Framework\View\Base\Element;
use Framework\UX\UI\Button;

#[AdminResource(
    name: 'posts',
    model: Post::class,
    title: '文章管理',
    icon: 'file-earmark-text',
    group: 'admin.content',
    sort: 11,
)]
class PostResource extends BaseResource
{
    public static function getName(): string
    {
        return 'posts';
    }

    public static function getModel(): string
    {
        return Post::class;
    }

    public static function getTitle(): string|array
    {
        return ['admin:posts.title', [], '文章管理'];
    }

    public static function getRoutePrefix(): ?string
    {
        return 'admin/posts';
    }

    public function configureForm(FormBuilder $form): void
    {
        $form->text('title', ['admin:posts.title_field', [], '标题'], ['required' => true])
            ->text('slug', ['admin:posts.slug', [], '标识'], [])
            ->textarea('excerpt', ['admin:posts.excerpt', [], '摘要'], [])
            ->textarea('content', ['admin:posts.content', [], '内容'], [])
            ->text('cover_image', ['admin:posts.cover_image', [], '封面图'], [])
            ->select('status', ['admin:posts.status', [], '状态'], [], [
                'draft' => t('admin:posts.draft'),
                'published' => t('admin:posts.published'),
                'archived' => t('admin:posts.archived'),
            ])
            ->number('category_id', ['admin:categories.title', [], '分类'], []);
    }

    public function configureTable(DataTable $table): void
    {
        $table->column('id', 'ID')
            ->column('title', ['admin:posts.title_field', [], '标题'])
            ->column('status', ['admin:posts.status', [], '状态'])
            ->column('created_at', ['admin:posts.created_at', [], '创建时间'])
            ->rowActions(function ($row, $rowKey, $index) {
                return [
                    Button::make()
                        ->label(t('admin.edit'))
                        ->secondary()
                        ->sm()
                        ->liveAction('editRow', 'click', ['rowKey' => $rowKey]),
                    Button::make()
                        ->label(t('admin.delete'))
                        ->danger()
                        ->sm()
                        ->liveAction('deleteRow', 'click', ['rowKey' => $rowKey]),
                ];
            });
    }

    public function getListBeforeTable(): mixed
    {
        return Element::make('div')->class('admin-list-stats')->intl('admin:posts.title');
    }

    public function getLiveActions(): array
    {
        return [];
    }
}
