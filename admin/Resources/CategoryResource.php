<?php

namespace Admin\Resources;

use Admin\Contracts\Resource\AdminResource;
use Admin\Contracts\Resource\BaseResource;
use Admin\Content\Category;
use Framework\UX\Form\FormBuilder;
use Framework\UX\Data\DataTable;
use Framework\View\Base\Element;
use Framework\UX\UI\Button;

#[AdminResource(
    name: 'categories',
    model: Category::class,
    title: '分类管理',
    icon: 'folder',
    group: 'admin.content',
    sort: 12,
)]
class CategoryResource extends BaseResource
{
    public static function getName(): string
    {
        return 'categories';
    }

    public static function getModel(): string
    {
        return Category::class;
    }

    public static function getTitle(): string|array
    {
        return ['admin:categories.title', [], '分类管理'];
    }

    public static function getRoutePrefix(): ?string
    {
        return 'admin/categories';
    }

    public function configureForm(FormBuilder $form): void
    {
        $form->text('name', ['admin:categories.name', [], '名称'], ['required' => true])
            ->text('slug', ['admin:categories.slug', [], '标识'], [])
            ->textarea('description', ['admin:categories.description', [], '描述'], [])
            ->number('sort', ['admin:categories.sort', [], '排序'], ['value' => '0']);
    }

    public function configureTable(DataTable $table): void
    {
        $table->column('id', 'ID')
            ->column('name', ['admin:categories.name', [], '名称'])
            ->column('slug', ['admin:categories.slug', [], '标识'])
            ->column('sort', ['admin:categories.sort', [], '排序'])
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
        return Element::make('div')->class('admin-list-stats')->intl('admin:categories.title');
    }

    public function getLiveActions(): array
    {
        return [];
    }
}
