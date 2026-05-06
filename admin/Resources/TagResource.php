<?php

namespace Admin\Resources;

use Admin\Contracts\Resource\AdminResource;
use Admin\Contracts\Resource\BaseResource;
use Admin\Content\Tag;
use Framework\UX\Form\FormBuilder;
use Framework\UX\Data\DataTable;
use Framework\View\Base\Element;
use Framework\UX\UI\Button;

#[AdminResource(
    name: 'tags',
    model: Tag::class,
    title: '标签管理',
    icon: 'tag',
    group: 'admin.content',
    sort: 13,
)]
class TagResource extends BaseResource
{
    public static function getName(): string
    {
        return 'tags';
    }

    public static function getModel(): string
    {
        return Tag::class;
    }

    public static function getTitle(): string|array
    {
        return ['admin:tags.title', [], '标签管理'];
    }

    public static function getRoutePrefix(): ?string
    {
        return 'admin/tags';
    }

    public function configureForm(FormBuilder $form): void
    {
        $form->text('name', ['admin:tags.name', [], '名称'], ['required' => true])
            ->text('slug', ['admin:tags.slug', [], '标识'], []);
    }

    public function configureTable(DataTable $table): void
    {
        $table->column('id', 'ID')
            ->column('name', ['admin:tags.name', [], '名称'])
            ->column('slug', ['admin:tags.slug', [], '标识'])
            ->rowActions(function ($row, $rowKey, $index) {
                return [
                    Button::make()
                        ->label(t('admin.edit'))
                        ->secondary()
                        ->sm()
                        ->liveAction('editRow', params: ['rowKey' => $rowKey]),
                    Button::make()
                        ->label(t('admin.delete'))
                        ->danger()
                        ->sm()
                        ->liveAction('deleteRow', params: ['rowKey' => $rowKey]),
                ];
            });
    }

    public function getListBeforeTable(): mixed
    {
        return Element::make('div')->class('admin-list-stats')->intl('admin:tags.title');
    }

    public function getLiveActions(): array
    {
        return [];
    }
}
