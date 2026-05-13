<?php

declare(strict_types=1);

namespace Shop\Resources;

use Admin\Contracts\Resource\AdminResource;
use Admin\Contracts\Resource\BaseResource;
use Shop\Models\Order;
use Framework\UX\Form\FormBuilder;
use Framework\UX\Form\Components\TextInput;
use Framework\UX\Form\Components\Select;
use Framework\UX\Form\Layout\Grid;
use Framework\UX\Form\Layout\Section;
use Framework\UX\Data\DataTable;
use Framework\UX\UI\Button;

#[AdminResource(
    name: 'shop-orders',
    model: Order::class,
    title: '订单管理',
    icon: 'cart-check',
    group: 'admin.shop',
    sort: 20,
)]
class OrderResource extends BaseResource
{
    public static function getName(): string
    {
        return 'shop-orders';
    }

    public static function getModel(): string
    {
        return Order::class;
    }

    public static function getTitle(): string|array
    {
        return '订单管理';
    }

    public static function getRoutePrefix(): ?string
    {
        return 'admin/shop-orders';
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
                Section::make('订单信息')->schema([
                    TextInput::make('order_number')->label('订单编号')->disabled(),
                    TextInput::make('customer_name')->label('客户名称'),
                    TextInput::make('customer_email')->label('邮箱'),
                    TextInput::make('customer_phone')->label('电话'),
                ]),
                Section::make('金额')->schema([
                    TextInput::make('subtotal')->label('小计'),
                    TextInput::make('discount')->label('优惠'),
                    TextInput::make('shipping_fee')->label('运费'),
                    TextInput::make('total')->label('合计'),
                ]),
            ]),
            Section::make('状态与配送')->schema([
                Select::make('status')->label('订单状态')->options([
                    'pending' => '待付款',
                    'paid' => '已付款',
                    'shipped' => '已发货',
                    'completed' => '已完成',
                    'cancelled' => '已取消',
                    'refunded' => '已退款',
                ]),
                TextInput::make('shipping_address')->label('配送地址'),
            ]),
        ]);
    }

    public function configureTable(DataTable $table): void
    {
        $table->column('id', 'ID')
            ->column('order_number', '订单编号')
            ->column('customer_name', '客户')
            ->column('total', '金额')
            ->column('status', '状态')
            ->column('created_at', '下单时间')
            ->rowActions(function ($row, $rowKey, $index) {
                return [
                    Button::make()->label('查看')->secondary()->sm()
                        ->liveAction('editRow', 'click', ['rowKey' => $rowKey]),
                ];
            });
    }

    public function getLiveActions(): array
    {
        return [];
    }
}