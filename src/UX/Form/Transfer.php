<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class Transfer extends UXComponent
{
    protected array $dataSource = [];
    protected array $targetKeys = [];
    protected ?string $titles = null;
    protected bool $disabled = false;
    protected bool $showSearch = false;
    protected ?string $action = null;
    protected ?string $searchPlaceholder = '请输入搜索内容';

    public function dataSource(array $data): static
    {
        $this->dataSource = $data;
        return $this;
    }

    public function targetKeys(array $keys): static
    {
        $this->targetKeys = $keys;
        return $this;
    }

    public function titles(string $left, string $right): static
    {
        $this->titles = json_encode([$left, $right]);
        return $this;
    }

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;
        return $this;
    }

    public function showSearch(bool $show = true): static
    {
        $this->showSearch = $show;
        return $this;
    }

    public function action(string $action): static
    {
        $this->action = $action;
        return $this;
    }

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
