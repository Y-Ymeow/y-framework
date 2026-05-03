<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 树形选择器
 *
 * 用于树形结构数据选择，支持多选、搜索、清除、禁用、空状态提示、Live 绑定。
 *
 * @ux-category Form
 * @ux-since 1.0.0
 * @ux-example TreeSelect::make()->treeData($deptTree)->placeholder('选择部门')
 * @ux-example TreeSelect::make()->treeData($roles)->multiple()->showSearch()->allowClear()
 * @ux-js-component tree-select.js
 * @ux-css tree-select.css
 */
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

    /**
     * 设置树形数据
     * @param array $data 树形数据数组（每项需包含 value, label/title, children 等）
     * @return static
     * @ux-example TreeSelect::make()->treeData($deptTree)
     */
    public function treeData(array $data): static
    {
        $this->treeData = $data;
        return $this;
    }

    /**
     * 设置选中值
     * @param string $value 选中值（多选时为逗号分隔的字符串）
     * @return static
     * @ux-example TreeSelect::make()->value('1')
     */
    public function value(string $value): static
    {
        $this->value = $value;
        return $this;
    }

    /**
     * 设置占位文本
     * @param string $placeholder 占位提示
     * @return static
     * @ux-example TreeSelect::make()->placeholder('选择部门')
     * @ux-default '请选择'
     */
    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    /**
     * 启用多选模式
     * @param bool $multiple 是否多选
     * @return static
     * @ux-example TreeSelect::make()->multiple()
     * @ux-default true
     */
    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;
        return $this;
    }

    /**
     * 设置禁用状态
     * @param bool $disabled 是否禁用
     * @return static
     * @ux-example TreeSelect::make()->disabled()
     * @ux-default true
     */
    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;
        return $this;
    }

    /**
     * 启用清除按钮
     * @param bool $allow 是否允许清除
     * @return static
     * @ux-example TreeSelect::make()->allowClear()
     * @ux-default true
     */
    public function allowClear(bool $allow = true): static
    {
        $this->allowClear = $allow;
        return $this;
    }

    /**
     * 启用搜索功能
     * @param bool $show 是否启用
     * @return static
     * @ux-example TreeSelect::make()->showSearch()
     * @ux-default true
     */
    public function showSearch(bool $show = true): static
    {
        $this->showSearch = $show;
        return $this;
    }

    /**
     * 设置 LiveAction（选择时触发）
     * @param string $action Action 名称
     * @return static
     * @ux-example TreeSelect::make()->action('selectNode')
     */
    public function action(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    /**
     * 设置空状态提示文字
     * @param string $text 提示文字
     * @return static
     * @ux-example TreeSelect::make()->emptyText('暂无数据')
     * @ux-default '暂无数据'
     */
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
