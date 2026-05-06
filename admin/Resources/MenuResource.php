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
        $form->text('title', ['admin:menus.title_field', [], '标题'], ['required' => true])
            ->text('url', ['admin:menus.url', [], '链接'], [])
            ->text('icon', ['admin:menus.icon', [], '图标'], [])
            ->number('sort', ['admin:menus.sort', [], '排序'], ['value' => '0'])
            ->text('permission', ['admin:menus.permission', [], '权限'], [])
            ->select('target', ['admin:menus.target', [], '打开方式'], [], [
                '_self' => '_self',
                '_blank' => '_blank',
            ]);
    }

    public function configureTable(DataTable $table): void
    {
        $table->column('id', 'ID')
            ->column('title', ['admin:menus.title_field', [], '标题'])
            ->column('url', ['admin:menus.url', [], '链接'])
            ->column('icon', ['admin:menus.icon', [], '图标'])
            ->column('permission', ['admin:menus.permission', [], '权限'])
            ->column('sort', ['admin:menus.sort', [], '排序'])
            ->column('is_active', ['admin:menus.is_active', [], '启用'])
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
        return Element::make('div')->class('admin-list-stats')->intl('admin:menus.title');
    }

    public function getLiveActions(): array
    {
        return [];
    }
}
