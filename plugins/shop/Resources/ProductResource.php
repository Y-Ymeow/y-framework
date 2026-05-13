<?php

declare(strict_types=1);

namespace Shop\Resources;

use Admin\Contracts\Resource\AdminResource;
use Admin\Contracts\Resource\BaseResource;
use Shop\Models\Product;
use Framework\UX\Form\FormBuilder;
use Framework\UX\Form\Components\TextInput;
use Framework\UX\Form\Components\Textarea;
use Framework\UX\Form\Components\Select;
use Framework\UX\Form\Layout\Grid;
use Framework\UX\Form\Layout\Section;
use Framework\UX\Data\DataTable;
use Framework\UX\UI\Button;

#[AdminResource(
    name: 'products',
    model: Product::class,
    title: '商品管理',
    icon: 'box',
    group: 'admin.shop',
    sort: 10,
)]
class ProductResource extends BaseResource
{
    public static function getName(): string
    {
        return 'products';
    }

    public static function getModel(): string
    {
        return Product::class;
    }

    public static function getTitle(): string|array
    {
        return '商品管理';
    }

    public static function getRoutePrefix(): ?string
    {
        return 'admin/products';
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
            Grid::make(2)->schema([
                Section::make('基本信息')->schema([
                    TextInput::make('name')->label('商品名称')->required(),
                    TextInput::make('slug')->label('标识'),
                    TextInput::make('sku')->label('SKU'),
                    TextInput::make('barcode')->label('条码'),
                    Select::make('status')->label('状态')->options([
                        'active' => '上架',
                        'draft' => '草稿',
                        'archived' => '下架',
                    ]),
                ]),
                Section::make('价格与库存')->schema([
                    TextInput::make('price')->label('售价')->required(),
                    TextInput::make('compare_price')->label('划线价'),
                    TextInput::make('cost_price')->label('成本价'),
                    TextInput::make('stock_quantity')->label('库存数量'),
                    TextInput::make('weight')->label('重量(kg)'),
                ]),
            ]),
            Section::make('描述')->schema([
                Textarea::make('description')->label('商品描述')->rows(6),
            ]),
        ]);
    }

    public function configureTable(DataTable $table): void
    {
        $table->column('id', 'ID')
            ->column('name', '商品名称')
            ->column('price', '售价')
            ->column('stock_quantity', '库存')
            ->column('status', '状态')
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