<?php

namespace Admin\Resources;

use Admin\Contracts\Resource\AdminResource;
use Admin\Contracts\Resource\BaseResource;
use Admin\Content\Post;
use Framework\UX\Form\FormBuilder;
use Framework\UX\Form\Components\TextInput;
use Framework\UX\Form\Components\Textarea;
use Framework\UX\Form\Components\Select;
use Framework\UX\Form\Layout\Grid;
use Framework\UX\Form\Layout\Section;
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
        $form->schema([
            Grid::make(2)->schema([
                Section::make('基本信息')->schema([
                    TextInput::make('title')
                        ->label(['admin:posts.title_field', [], '标题'])
                        ->required(),

                    TextInput::make('slug')
                        ->label(['admin:posts.slug', [], '标识']),

                    Select::make('status')
                        ->label(['admin:posts.status', [], '状态'])
                        ->options([
                            'draft' => t('admin:posts.draft'),
                            'published' => t('admin:posts.published'),
                            'archived' => t('admin:posts.archived'),
                        ]),

                    TextInput::make('category_id')
                        ->label(['admin:categories.title', [], '分类'])
                        ->number(),
                ]),

                Section::make('媒体')->schema([
                    TextInput::make('cover_image')
                        ->label(['admin:posts.cover_image', [], '封面图']),
                ]),
            ]),

            Section::make('内容')->schema([
                Textarea::make('excerpt')
                    ->label(['admin:posts.excerpt', [], '摘要'])
                    ->rows(3),

                \Framework\UX\Form\BlockEditor::make('content')
                    ->label(['admin:posts.content', [], '内容']),
            ]),
        ]);
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
