<?php

declare(strict_types=1);

namespace Admin\Resources;

use Framework\Admin\Attribute\AdminResource;
use Framework\Admin\Resource\BaseResource;
use Framework\UX\Form\FormBuilder;
use Framework\UX\Data\DataTable;
use Framework\UX\Data\DataTableColumn;
use Framework\UX\UI\Badge;
use Framework\UX\UI\Button;
use Framework\UX\UI\Navigate;
use Framework\Database\Model;

#[AdminResource(
    name: 'products',
    model: Product::class,
    title: '商品管理',
    icon: 'heroicon.shopping-bag',
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

    public static function getTitle(): string
    {
        return '商品管理';
    }

    public static function getRoutes(): array
    {
        $routes = parent::getRoutes();
        $name = static::getName();

        // 注册资源自定义页面: /admin/products/stats
        $routes["admin.resource.{$name}.stats"] = [
            'method' => 'GET',
            'path' => "/{$name}/stats",
            'handler' => static::page(\Admin\Pages\UxDemoPage::class), // 暂时借用 UxDemoPage 演示
        ];

        return $routes;
    }

    public function getHeader(): mixed
    {
        return null;
    }

    public function getFooter(): mixed
    {
        return null;
    }

    public function configureForm(FormBuilder $form): void
    {
        $form->text('name', '商品名称', ['required' => true])
            ->textarea('description', '商品描述')
            ->number('price', '价格', ['required' => true, 'step' => '0.01'])
            ->number('stock', '库存', ['required' => true, 'min' => '0'])
            ->select('status', '状态', [], [
                'active' => '上架',
                'inactive' => '下架',
                'draft' => '草稿',
            ])
            ->select('category', '分类', [], [
                'electronics' => '电子产品',
                'clothing' => '服装',
                'food' => '食品',
                'books' => '图书',
                'other' => '其他',
            ]);
    }

    public function configureTable(DataTable $table): void
    {
        $table->addColumn(
            DataTableColumn::make('id', 'ID')
                ->width('60px')
                ->sortable()
        );
        $table->addColumn(
            DataTableColumn::make('name', '商品名称')
                ->sortable()
                ->searchLike()
        );
        $table->addColumn(
            DataTableColumn::make('category', '分类')
                ->render(function ($value) {
                    $map = [
                        'electronics' => '电子产品',
                        'clothing' => '服装',
                        'food' => '食品',
                        'books' => '图书',
                        'other' => '其他',
                    ];
                    return $map[$value] ?? $value;
                })
                ->searchIn([
                    'electronics' => '电子产品',
                    'clothing' => '服装',
                    'food' => '食品',
                    'books' => '图书',
                    'other' => '其他',
                ])
        );
        $table->addColumn(
            DataTableColumn::make('price', '价格')
                ->render(function ($value) {
                    return '¥' . number_format((float)$value, 2);
                })
                ->alignRight()
                ->sortable()
        );
        $table->addColumn(
            DataTableColumn::make('stock', '库存')
                ->alignCenter()
                ->sortable()
        );
        $table->addColumn(
            DataTableColumn::make('status', '状态')
                ->render(function ($value) {
                    $config = match ($value) {
                        'active' => ['text' => '上架', 'variant' => 'success'],
                        'inactive' => ['text' => '下架', 'variant' => 'danger'],
                        'draft' => ['text' => '草稿', 'variant' => 'warning'],
                        default => ['text' => $value, 'variant' => 'default'],
                    };
                    return Badge::make($config['text'])->{$config['variant']}()->sm()->render();
                })
                ->alignCenter()
                ->searchEqual()
        );
        $table->addColumn(
            DataTableColumn::make('created_at', '创建时间')
                ->sortable()
                ->width('160px')
        );
        $table->rowActions(function ($row, $rowKey, $index) {
            return [
                Navigate::make()
                    ->href(recordEditUrl('products', $rowKey))
                    ->text('编辑')
                    ->bi('pencil')
                    ->secondary()
                    ->sm(),
                Button::make()
                    ->label('删除')
                    ->danger()
                    ->sm()
                    ->icon('trash', 'left')
                    ->liveAction('deleteRow')
                    ->data('action-params', json_encode(['rowKey' => $rowKey]))
                    ->data('confirm', '确定删除此记录？'),
            ];
        });
    }

    /**
     * 手动注册 LiveActions
     */
    public function getLiveActions(): array
    {
        return [
            // 简写形式: actionName => methodName
            'bulkPublish' => 'handleBulkPublish',

            // 完整配置形式
            'quickEdit' => [
                'method' => 'handleQuickEdit',
                'event' => 'click',
            ],
        ];
    }
}

class Product extends Model
{
    protected string $table = 'products';

    protected array $fillable = ['name', 'description', 'price', 'stock', 'status', 'category'];

    public static function boot(): void
    {
        static::creating(function ($model) {
            if (!isset($model->created_at)) {
                $model->created_at = date('Y-m-d H:i:s');
            }
        });
    }
}
