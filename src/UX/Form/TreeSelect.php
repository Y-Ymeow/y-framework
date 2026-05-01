<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class TreeSelect extends UXComponent
{
    protected array $treeData = [];
    protected ?string $value = null;
    protected ?string $placeholder = '请选择';
    protected bool $multiple = false;
    protected bool $disabled = false;
    protected bool $allowClear = true;
    protected bool $showSearch = false;
    protected ?string $action = null;
    protected ?string $emptyText = '暂无数据';

    public function treeData(array $data): static
    {
        $this->treeData = $data;
        return $this;
    }

    public function value(string $value): static
    {
        $this->value = $value;
        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;
        return $this;
    }

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;
        return $this;
    }

    public function allowClear(bool $allow = true): static
    {
        $this->allowClear = $allow;
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

    public function emptyText(string $text): static
    {
        $this->emptyText = $text;
        return $this;
    }

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-tree-select');
        if ($this->disabled) {
            $el->class('ux-tree-select-disabled');
        }
        if ($this->multiple) {
            $el->class('ux-tree-select-multiple');
        }

        $el->data('tree-data', json_encode($this->treeData));
        $el->data('tree-value', $this->value ?? '');

        if ($this->action) {
            $el->data('tree-action', $this->action);
        }

        // 选择框
        $selectorEl = Element::make('div')->class('ux-tree-select-selector');

        // 搜索/显示区域
        if ($this->showSearch) {
            $searchEl = Element::make('input')
                ->attr('type', 'text')
                ->attr('placeholder', $this->placeholder)
                ->class('ux-tree-select-search');
            $selectorEl->child($searchEl);
        } else {
            $displayText = $this->getDisplayText();
            $displayEl = Element::make('span')
                ->class('ux-tree-select-display')
                ->class($displayText === $this->placeholder ? 'placeholder' : '')
                ->text($displayText);
            $selectorEl->child($displayEl);
        }

        // 清除按钮
        if ($this->allowClear && $this->value) {
            $clearEl = Element::make('span')
                ->class('ux-tree-select-clear')
                ->html('<i class="bi bi-x-circle"></i>');
            $selectorEl->child($clearEl);
        }

        // 下拉箭头
        $arrowEl = Element::make('span')
            ->class('ux-tree-select-arrow')
            ->html('<i class="bi bi-chevron-down"></i>');
        $selectorEl->child($arrowEl);

        $el->child($selectorEl);

        // 生成树形下拉面板
        $el->child($this->generateTreeDropdown());

        // Live 桥接隐藏 input
        $liveInput = $this->createLiveModelInput($this->value ?? '');
        if ($liveInput) {
            $el->child($liveInput);
        }

        return $el;
    }

    protected function generateTreeDropdown(): Element
    {
        $dropdown = Element::make('div')->class('ux-tree-select-dropdown');
        
        if (empty($this->treeData)) {
            $dropdown->child(
                Element::make('div')
                    ->class('ux-tree-select-empty')
                    ->text($this->emptyText)
            );
            return $dropdown;
        }

        $treeEl = Element::make('div')->class('ux-tree-select-tree');
        $this->renderTreeNodes($treeEl, $this->treeData);
        $dropdown->child($treeEl);

        return $dropdown;
    }

    protected function renderTreeNodes(Element $parent, array $nodes, int $level = 0): void
    {
        foreach ($nodes as $node) {
            $nodeEl = Element::make('div')
                ->class('ux-tree-select-node')
                ->data('node-value', $node['value'] ?? '');

            $contentEl = Element::make('div')
                ->class('ux-tree-select-node-content')
                ->style("padding-left: " . ($level * 16) . "px");

            // 展开/折叠图标
            if (!empty($node['children'])) {
                $toggleEl = Element::make('span')
                    ->class('ux-tree-select-node-toggle')
                    ->html('<i class="bi bi-chevron-right"></i>');
                $contentEl->child($toggleEl);
            } else {
                $contentEl->child(Element::make('span')->class('ux-tree-select-node-toggle leaf'));
            }

            // 复选框（多选模式）
            if ($this->multiple) {
                $checkboxEl = Element::make('span')
                    ->class('ux-tree-select-checkbox')
                    ->class($this->isNodeSelected($node) ? 'checked' : '');
                if ($this->isNodeSelected($node)) {
                    $checkboxEl->html('<i class="bi bi-check"></i>');
                }
                $contentEl->child($checkboxEl);
            }

            // 节点标题
            $titleEl = Element::make('span')
                ->class('ux-tree-select-node-title')
                ->text($node['label'] ?? $node['title'] ?? '');
            $contentEl->child($titleEl);

            $nodeEl->child($contentEl);

            // 子节点
            if (!empty($node['children'])) {
                $childrenEl = Element::make('div')
                    ->class('ux-tree-select-children')
                    ->class('collapsed');
                $this->renderTreeNodes($childrenEl, $node['children'], $level + 1);
                $nodeEl->child($childrenEl);
            }

            $parent->child($nodeEl);
        }
    }

    protected function isNodeSelected(array $node): bool
    {
        if (!$this->value) {
            return false;
        }
        $values = $this->multiple ? explode(',', $this->value) : [$this->value];
        return in_array($node['value'] ?? '', $values);
    }

    protected function getDisplayText(): string
    {
        if (!$this->value) {
            return $this->placeholder;
        }

        $values = $this->multiple ? explode(',', $this->value) : [$this->value];
        $labels = [];

        foreach ($values as $val) {
            $label = $this->findLabelInTree($this->treeData, $val);
            if ($label) {
                $labels[] = $label;
            }
        }

        return empty($labels) ? $this->placeholder : implode(', ', $labels);
    }

    protected function findLabelInTree(array $tree, string $value): ?string
    {
        foreach ($tree as $node) {
            if (($node['value'] ?? '') === $value) {
                return $node['label'] ?? $node['title'] ?? $value;
            }
            if (!empty($node['children'])) {
                $found = $this->findLabelInTree($node['children'], $value);
                if ($found) {
                    return $found;
                }
            }
        }
        return null;
    }
}
