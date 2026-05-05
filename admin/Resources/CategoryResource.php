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
    group: '内容管理',
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
        $form->text('name', t('admin:categories.name'), ['required' => true])
            ->text('slug', t('admin:categories.slug'), [])
            ->textarea('description', t('admin:categories.description'), [])
            ->number('sort', t('admin:categories.sort'), ['value' => '0']);
    }

    public function configureTable(DataTable $table): void
    {
        $table->column('id', 'ID')
            ->column('name', t('admin:categories.name'))
            ->column('slug', t('admin:categories.slug'))
            ->column('sort', t('admin:categories.sort'))
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
