<?php

namespace Admin\Resources;

use Admin\Contracts\Resource\AdminResource;
use Admin\Contracts\Resource\BaseResource;
use Admin\Content\Menu;
use Admin\Content\MenuItem;
use Framework\UX\Form\FormBuilder;
use Framework\UX\Data\DataTable;
use Framework\View\Base\Element;
use Framework\UX\UI\Button;

#[AdminResource(
    name: 'menus',
    model: MenuItem::class,
    title: '菜单管理',
    icon: 'list',
    group: 'admin.system',
    sort: 53,
)]
class MenuResource extends BaseResource
{
    public static function getName(): string
    {
        return 'menus';
    }

    public static function getModel(): string
    {
        return MenuItem::class;
    }

    public static function getTitle(): string|array
    {
        return ['admin:menus.title', [], '菜单管理'];
    }

    public static function getRoutePrefix(): ?string
    {
        return 'admin/menus';
    }

    public function configureForm(FormBuilder $form): void
    {
        $form->text('title', t('admin:menus.title_field'), ['required' => true])
            ->text('url', t('admin:menus.url'), [])
            ->text('icon', t('admin:menus.icon'), [])
            ->number('sort', t('admin:menus.sort'), ['value' => '0'])
            ->text('permission', t('admin:menus.permission'), [])
            ->select('target', t('admin:menus.target'), [], [
                '_self' => '_self',
                '_blank' => '_blank',
            ]);
    }

    public function configureTable(DataTable $table): void
    {
        $table->column('id', 'ID')
            ->column('title', t('admin:menus.title_field'))
            ->column('url', t('admin:menus.url'))
            ->column('icon', t('admin:menus.icon'))
            ->column('permission', t('admin:menus.permission'))
            ->column('sort', t('admin:menus.sort'))
            ->column('is_active', t('admin:menus.is_active'))
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
        return Element::make('div')->class('admin-list-stats')->intl('admin:menus.title');
    }

    public function getLiveActions(): array
    {
        return [];
    }
}
