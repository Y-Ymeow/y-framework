<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 穿梭框
 *
 * 用于双栏数据穿梭选择，支持数据源、目标项、标题、搜索、禁用、Live 绑定。
 *
 * @ux-category Form
 * @ux-since 1.0.0
 * @ux-example Transfer::make()->dataSource($users)->targetKeys([1, 2, 3])->titles('可选', '已选')
 * @ux-example Transfer::make()->dataSource($items)->targetKeys($selected)->showSearch()->searchPlaceholder('搜索...')
 * @ux-js-component transfer.js
 * @ux-css transfer.css
 */
class Transfer extends UXComponent
{
    protected array $dataSource = [];
    protected array $targetKeys = [];
    protected ?string $titles = null;
    protected bool $disabled = false;
    protected bool $showSearch = false;
    protected ?string $action = null;
    protected ?string $searchPlaceholder = '请输入搜索内容';

    /**
     * 设置数据源
     * @param array $data 数据数组（每项需包含 key/value, title/label/name 等）
     * @return static
     * @ux-example Transfer::make()->dataSource($users)
     */
    public function dataSource(array $data): static
    {
        $this->dataSource = $data;
        return $this;
    }

    /**
     * 设置目标项（已选中的项）
     * @param array $keys 选中项的 key 数组
     * @return static
     * @ux-example Transfer::make()->targetKeys([1, 2, 3])
     */
    public function targetKeys(array $keys): static
    {
        $this->targetKeys = $keys;
        return $this;
    }

    /**
     * 设置左右面板标题
     * @param string $left 左侧标题
     * @param string $right 右侧标题
     * @return static
     * @ux-example Transfer::make()->titles('可选', '已选')
     */
    public function titles(string $left, string $right): static
    {
        $this->titles = json_encode([$left, $right]);
        return $this;
    }

    /**
     * 设置禁用状态
     * @param bool $disabled 是否禁用
     * @return static
     * @ux-example Transfer::make()->disabled()
     * @ux-default true
     */
    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;
        return $this;
    }

    /**
     * 启用搜索功能
     * @param bool $show 是否启用
     * @return static
     * @ux-example Transfer::make()->showSearch()
     * @ux-default true
     */
    public function showSearch(bool $show = true): static
    {
        $this->showSearch = $show;
        return $this;
    }

    /**
     * 设置 LiveAction（穿梭时触发）
     * @param string $action Action 名称
     * @return static
     * @ux-example Transfer::make()->action('transferItems')
     */
    public function action(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    /**
     * 设置搜索占位文本
     * @param string $placeholder 占位提示
     * @return static
     * @ux-example Transfer::make()->searchPlaceholder('搜索...')
     * @ux-default '请输入搜索内容'
     */
    public function searchPlaceholder(string $placeholder): static
    {
        $this->searchPlaceholder = $placeholder;
        return $this;
    }

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-transfer');
        if ($this->disabled) {
            $el->class('ux-transfer-disabled');
        }

        $el->data('transfer-data', json_encode($this->dataSource));
        $el->data('transfer-target', json_encode($this->targetKeys));

        if ($this->action) {
            $el->data('transfer-action', $this->action);
        }

        // 左侧面板
        $leftPanel = $this->createPanel('left', $this->titles ? json_decode($this->titles)[0] : '源列表');
        $el->child($leftPanel);

        // 中间操作区
        $operationsEl = Element::make('div')->class('ux-transfer-operations');
        $operationsEl->child(
            Element::make('button')
                ->class('ux-transfer-btn')
                ->class('ux-transfer-btn-right')
                ->attr('disabled', 'true')
                ->html('<i class="bi bi-chevron-right"></i>')
        );
        $operationsEl->child(
            Element::make('button')
                ->class('ux-transfer-btn')
                ->class('ux-transfer-btn-left')
                ->attr('disabled', 'true')
                ->html('<i class="bi bi-chevron-left"></i>')
        );
        $el->child($operationsEl);

        // 右侧面板
        $rightPanel = $this->createPanel('right', $this->titles ? json_decode($this->titles)[1] : '目标列表');
        $el->child($rightPanel);

        // Live 桥接隐藏 input
        $liveInput = $this->createLiveModelInput(json_encode($this->targetKeys));
        if ($liveInput) {
            $el->child($liveInput);
        }

        return $el;
    }

    protected function createPanel(string $side, string $title): Element
    {
        $panelEl = Element::make('div')->class('ux-transfer-panel');

        // 分离源数据和目标数据
        $sourceItems = [];
        $targetItems = [];
        
        foreach ($this->dataSource as $item) {
            $key = $item['key'] ?? $item['value'] ?? '';
            if (in_array($key, $this->targetKeys)) {
                $targetItems[] = $item;
            } else {
                $sourceItems[] = $item;
            }
        }
        
        $items = $side === 'left' ? $sourceItems : $targetItems;
        $totalCount = count($side === 'left' ? $sourceItems : $targetItems);

        // 头部
        $headerEl = Element::make('div')->class('ux-transfer-panel-header');
        $headerEl->child(
            Element::make('label')
                ->class('ux-transfer-panel-checkbox')
                ->html('<input type="checkbox" class="ux-transfer-panel-check-all" data-side="' . $side . '">')
        );
        $headerEl->child(
            Element::make('span')
                ->class('ux-transfer-panel-title')
                ->text($title)
        );
        $headerEl->child(
            Element::make('span')
                ->class('ux-transfer-panel-count')
                ->text((string)$totalCount)
        );
        $panelEl->child($headerEl);

        // 搜索框
        if ($this->showSearch) {
            $searchEl = Element::make('div')->class('ux-transfer-panel-search');
            $searchEl->child(
                Element::make('input')
                    ->attr('type', 'text')
                    ->attr('placeholder', $this->searchPlaceholder)
                    ->class('ux-transfer-panel-search-input')
                    ->data('side', $side)
            );
            $searchEl->child(
                Element::make('span')
                    ->class('ux-transfer-panel-search-icon')
                    ->html('<i class="bi bi-search"></i>')
            );
            $panelEl->child($searchEl);
        }

        // 列表 - 服务器端渲染列表项
        $listEl = Element::make('div')
            ->class('ux-transfer-panel-list')
            ->data('side', $side);
        
        if (empty($items)) {
            $listEl->child(
                Element::make('div')
                    ->class('ux-transfer-panel-empty')
                    ->text('暂无数据')
            );
        } else {
            foreach ($items as $item) {
                $key = $item['key'] ?? $item['value'] ?? '';
                $label = $item['title'] ?? $item['label'] ?? $item['name'] ?? '';
                $disabled = $item['disabled'] ?? false;
                
                $itemEl = Element::make('div')
                    ->class('ux-transfer-item')
                    ->data('key', (string)$key)
                    ->data('side', $side);
                
                if ($disabled) {
                    $itemEl->class('ux-transfer-item-disabled');
                }
                
                $itemEl->child(
                    Element::make('label')
                        ->class('ux-transfer-item-checkbox')
                        ->html('<input type="checkbox"' . ($disabled ? ' disabled' : '') . '>')
                );
                $itemEl->child(
                    Element::make('span')
                        ->class('ux-transfer-item-label')
                        ->text($label)
                );
                
                $listEl->child($itemEl);
            }
        }
        
        $panelEl->child($listEl);

        // 底部
        $footerEl = Element::make('div')->class('ux-transfer-panel-footer');
        $panelEl->child($footerEl);

        return $panelEl;
    }
}
