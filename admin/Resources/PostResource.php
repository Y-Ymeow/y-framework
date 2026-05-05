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
    group: '内容管理',
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
        $form->text('title', t('admin:posts.title_field'), ['required' => true])
            ->text('slug', t('admin:posts.slug'), [])
            ->textarea('excerpt', t('admin:posts.excerpt'), [])
            ->textarea('content', t('admin:posts.content'), [])
            ->text('cover_image', t('admin:posts.cover_image'), [])
            ->select('status', t('admin:posts.status'), [], [
                'draft' => t('admin:posts.draft'),
                'published' => t('admin:posts.published'),
                'archived' => t('admin:posts.archived'),
            ])
            ->number('category_id', t('admin:categories.title'), []);
    }

    public function configureTable(DataTable $table): void
    {
        $table->column('id', 'ID')
            ->column('title', t('admin:posts.title_field'))
            ->column('status', t('admin:posts.status'))
            ->column('created_at', t('admin:posts.created_at'))
            ->rowActions(function ($row, $rowKey, $index) {
                return [
                    Button::make()
                        ->label(t('admin.edit'))
                        ->secondary()
                        ->sm()
                        ->liveAction('editRow')
                        ->data('action-params', json_encode(['rowKey' => $rowKey])),
                    Button::make()
                        ->label(t('admin.delete'))
                        ->danger()
                        ->sm()
                        ->liveAction('deleteRow')
                        ->data('action-params', json_encode(['rowKey' => $rowKey])),
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
