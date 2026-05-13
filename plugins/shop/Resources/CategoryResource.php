<?php

declare(strict_types=1);

namespace Shop\Resources;

use Admin\Contracts\Resource\AdminResource;
use Admin\Contracts\Resource\BaseResource;
use Shop\Models\ProductCategory;
use Framework\UX\Form\FormBuilder;
use Framework\UX\Form\Components\TextInput;
use Framework\UX\Form\Components\Textarea;
use Framework\UX\Form\Layout\Grid;
use Framework\UX\Form\Layout\Section;
use Framework\UX\Data\DataTable;
use Framework\UX\UI\Button;

#[AdminResource(
    name: 'shop-categories',
    model: ProductCategory::class,
    title: '商品分类',
    icon: 'tags',
    group: 'admin.shop',
    sort: 5,
)]
class CategoryResource extends BaseResource
{
    public static function getName(): string
    {
        return 'shop-categories';
    }

    public static function getModel(): string
    {
        return ProductCategory::class;
    }

    public static function getTitle(): string|array
    {
        return '商品分类';
    }

    public static function getRoutePrefix(): ?string
    {
        return 'admin/shop-categories';
    }

    public static function getRoutes(): array
    {
        $name = static::getName();

        return [
            "admin.resource.{$name}" => [
                'method' => 'GET',
                'path' => "/{$name}",
                'handler' => \Admin\Contracts\Live\AdminListPage::resource($name),
            ],
            "admin.resource.{$name}.create" => [
                'method' => 'GET',
                'path' => "/{$name}/create",
                'handler' => \Admin\Contracts\Live\AdminFormPage::resource($name),
            ],
            "admin.resource.{$name}.edit" => [
                'method' => 'GET',
                'path' => "/{$name}/{id}/edit",
                'handler' => \Admin\Contracts\Live\AdminFormPage::resource($name),
            ],
        ];
    }

    public function configureForm(FormBuilder $form): void
    {
        $form->schema([
            Section::make('分类信息')->schema([
                TextInput::make('name')->label('分类名称')->required(),
                TextInput::make('slug')->label('标识'),
                TextInput::make('parent_id')->label('父级ID'),
                TextInput::make('sort_order')->label('排序'),
            ]),
            Section::make('描述')->schema([
                Textarea::make('description')->label('分类描述')->rows(4),
            ]),
        ]);
    }

    public function configureTable(DataTable $table): void
    {
        $table->column('id', 'ID')
            ->column('name', '分类名称')
            ->column('slug', '标识')
            ->column('sort_order', '排序')
            ->column('created_at', '创建时间')
            ->rowActions(function ($row, $rowKey, $index) {
                return [
                    Button::make()->label('编辑')->secondary()->sm()
                        ->liveAction('editRow', 'click', ['rowKey' => $rowKey]),
                    Button::make()->label('删除')->danger()->sm()
                        ->liveAction('deleteRow', 'click', ['rowKey' => $rowKey]),
                ];
            });
    }

    public function getLiveActions(): array
    {
        return [];
    }
}