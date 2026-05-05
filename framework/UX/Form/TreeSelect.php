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
    protected static ?string $componentName = 'treeSelect';

    protected array $treeData = [];
    protected ?string $value = null;
    protected ?string $placeholder = '请选择';
    protected bool $multiple = false;
    protected bool $disabled = false;
    protected bool $allowClear = true;
    protected bool $showSearch = false;
    protected ?string $action = null;
    protected ?string $emptyText = '暂无数据';

    protected function init(): void
    {
        $this->registerJs('treeSelect', '
            const TreeSelect = {
                init() {
                    document.querySelectorAll(".ux-tree-select").forEach(select => {
                        const value = select.dataset.treeValue;
                        if (value) {
                            const multiple = select.classList.contains("ux-tree-select-multiple");
                            if (multiple) {
                                value.split(",").forEach(v => {
                                    const node = select.querySelector(\'.ux-tree-select-node[data-node-value="\' + v + \'"]\');
                                    if (node) node.classList.add("selected");
                                });
                            } else {
                                const display = select.querySelector(".ux-tree-select-display");
                                const searchInput = select.querySelector(".ux-tree-select-search");
                                const node = select.querySelector(\'.ux-tree-select-node[data-node-value="\' + value + \'"]\');
                                const titleText = node?.querySelector(".ux-tree-select-node-title")?.textContent || "";
                                if (display) {
                                    display.textContent = titleText;
                                    display.classList.remove("placeholder");
                                }
                                if (searchInput) {
                                    searchInput.value = titleText;
                                }
                                if (node) node.classList.add("selected");
                            }
                        }
                    });

                    document.addEventListener("mousedown", (e) => {
                        if (!e.target || !e.target.closest) return;

                        const clear = e.target.closest(".ux-tree-select-clear");
                        if (clear) { e.preventDefault(); e.stopPropagation(); const s = clear.closest(".ux-tree-select"); if (s) this.clear(s); return; }

                        const titleEl = e.target.closest(".ux-tree-select-node-title");
                        if (titleEl) {
                            e.preventDefault();
                            e.stopPropagation();
                            const node = titleEl.closest(".ux-tree-select-node");
                            const sel = node?.closest(".ux-tree-select");
                            if (sel) this.selectNode(sel, node);
                            return;
                        }

                        const toggleEl = e.target.closest(".ux-tree-select-node-toggle:not(.leaf)");
                        if (toggleEl) {
                            e.stopPropagation();
                            const node = toggleEl.closest(".ux-tree-select-node");
                            if (node) this.toggleNode(node);
                            return;
                        }

                        const selectorEl = e.target.closest(".ux-tree-select-selector");
                        if (selectorEl && !e.target.closest(".ux-tree-select-dropdown")) {
                            const sel = selectorEl.closest(".ux-tree-select");
                            if (sel) { this.toggle(sel); return; }
                        }

                        if (!e.target.closest(".ux-tree-select")) this.hideAll();
                    });

                    document.addEventListener("click", (e) => {
                        if (!e.target || !e.target.closest) return;
                    });

                    document.addEventListener("input", (e) => {
                        if (!e.target || !e.target.classList.contains("ux-tree-select-search")) return;
                        const searchInput = e.target;
                        const select = searchInput.closest(".ux-tree-select");
                        if (select) this.filter(select, searchInput.value);
                    });
                },
                toggle(select) {
                    if (select.classList.contains("ux-tree-select-open")) this.hide(select);
                    else this.show(select);
                },
                show(select) {
                    this.hideAll();
                    select.classList.add("ux-tree-select-open");
                    const dropdown = select.querySelector(".ux-tree-select-dropdown");
                    if (dropdown) {
                        dropdown.classList.add("show");
                        dropdown.style.pointerEvents = "auto";
                    }
                },
                hide(select) {
                    select.classList.remove("ux-tree-select-open");
                    const dropdown = select.querySelector(".ux-tree-select-dropdown");
                    if (dropdown) dropdown.classList.remove("show");
                },
                hideAll() {
                    document.querySelectorAll(".ux-tree-select-open").forEach(s => this.hide(s));
                },
                toggleNode(node) {
                    if (!node) return;
                    const children = node.querySelector(":scope > .ux-tree-select-children");
                    if (!children) return;
                    const icon = node.querySelector(":scope > .ux-tree-select-node-content > .ux-tree-select-node-toggle i");
                    if (children.classList.contains("collapsed")) {
                        children.classList.remove("collapsed");
                        if (icon) icon.className = "bi bi-chevron-down";
                    } else {
                        children.classList.add("collapsed");
                        if (icon) icon.className = "bi bi-chevron-right";
                    }
                },
                selectNode(select, node) {
                    const value = node.dataset.nodeValue;
                    if (!value) return;
                    const multiple = select.classList.contains("ux-tree-select-multiple");
                    if (multiple) {
                        const values = select.dataset.treeValue ? select.dataset.treeValue.split(",") : [];
                        const idx = values.indexOf(value);
                        if (idx > -1) {
                            values.splice(idx, 1);
                            node.classList.remove("selected");
                        } else {
                            values.push(value);
                            node.classList.add("selected");
                        }
                        select.dataset.treeValue = values.join(",");
                    } else {
                        select.dataset.treeValue = value;
                        const titleText = node.querySelector(".ux-tree-select-node-title")?.textContent || "";
                        const display = select.querySelector(".ux-tree-select-display");
                        const searchInput = select.querySelector(".ux-tree-select-search");
                        if (display) {
                            display.textContent = titleText;
                            display.classList.remove("placeholder");
                        }
                        if (searchInput) {
                            searchInput.value = titleText;
                        }
                        select.querySelectorAll(".ux-tree-select-node.selected").forEach(n => n.classList.remove("selected"));
                        node.classList.add("selected");
                        this.hide(select);
                    }
                    select.dispatchEvent(new CustomEvent("ux:change", { detail: { value: select.dataset.treeValue }, bubbles: true }));
                },
                clear(select) {
                    select.dataset.treeValue = "";
                    const display = select.querySelector(".ux-tree-select-display");
                    const searchInput = select.querySelector(".ux-tree-select-search");
                    if (display) {
                        display.textContent = display.dataset.placeholder || "请选择";
                        display.classList.add("placeholder");
                    }
                    if (searchInput) {
                        searchInput.value = "";
                    }
                    select.querySelectorAll(".ux-tree-select-node.selected").forEach(n => n.classList.remove("selected"));
                    select.dispatchEvent(new CustomEvent("ux:change", { detail: { value: "" }, bubbles: true }));
                },
                filter(select, keyword) {
                    const nodes = select.querySelectorAll(".ux-tree-select-node");
                    keyword = keyword.toLowerCase();
                    nodes.forEach(node => {
                        const title = (node.querySelector(".ux-tree-select-node-title")?.textContent || "").toLowerCase();
                        const match = title.includes(keyword);
                        node.style.display = match ? "" : "none";
                        if (match) {
                            let parent = node.parentElement?.closest(".ux-tree-select-node");
                            while (parent) {
                                parent.style.display = "";
                                const children = parent.querySelector(":scope > .ux-tree-select-children");
                                if (children) children.classList.remove("collapsed");
                                parent = parent.parentElement?.closest(".ux-tree-select-node");
                            }
                        }
                    });
                }
            };
            return TreeSelect;
        ');

        $this->registerCss(<<<'CSS'
.ux-tree-select {
    position: relative;
    display: inline-block;
    min-width: 12rem;
}
.ux-tree-select-disabled {
    opacity: 0.5;
    pointer-events: none;
}
.ux-tree-select-selector {
    display: flex;
    align-items: center;
    padding: 0.375rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    background: #fff;
    cursor: pointer;
    min-height: 2.25rem;
    transition: border-color 0.15s, box-shadow 0.15s;
}
.ux-tree-select-selector:hover {
    border-color: #9ca3af;
}
.ux-tree-select-open .ux-tree-select-selector {
    border-color: #3b82f6;
    box-shadow: 0 0 0 2px rgba(59,130,246,0.15);
}
.ux-tree-select-display {
    flex: 1;
    font-size: 0.875rem;
    color: #374151;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.ux-tree-select-display.placeholder {
    color: #9ca3af;
}
.ux-tree-select-search {
    flex: 1;
    border: none;
    outline: none;
    font-size: 0.875rem;
    color: #374151;
    background: transparent;
    padding: 0;
    min-width: 0;
}
.ux-tree-select-search::placeholder {
    color: #9ca3af;
}
.ux-tree-select-clear {
    display: inline-flex;
    align-items: center;
    color: #9ca3af;
    cursor: pointer;
    padding: 0 0.25rem;
    font-size: 0.75rem;
    transition: color 0.15s;
}
.ux-tree-select-clear:hover {
    color: #6b7280;
}
.ux-tree-select-arrow {
    display: inline-flex;
    align-items: center;
    color: #9ca3af;
    font-size: 0.75rem;
    margin-left: 0.25rem;
    transition: transform 0.2s;
}
.ux-tree-select-open .ux-tree-select-arrow {
    transform: rotate(180deg);
}
.ux-tree-select-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    z-index: 50;
    margin-top: 0.25rem;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    max-height: 16rem;
    overflow-y: auto;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-4px);
    transition: opacity 0.15s, transform 0.15s, visibility 0.15s;
}
.ux-tree-select-dropdown.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}
.ux-tree-select-empty {
    padding: 1.5rem;
    text-align: center;
    color: #9ca3af;
    font-size: 0.8125rem;
}
.ux-tree-select-tree {
    padding: 0.25rem 0;
}
.ux-tree-select-node {
    font-size: 0.8125rem;
}
.ux-tree-select-node-content {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    cursor: pointer;
    color: #374151;
    transition: background-color 0.1s;
}
.ux-tree-select-node-content:hover {
    background: #f9fafb;
}
.ux-tree-select-node.selected > .ux-tree-select-node-content {
    background: #eff6ff;
    color: #1d4ed8;
}
.ux-tree-select-node-toggle {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1rem;
    height: 1rem;
    color: #9ca3af;
    cursor: pointer;
    flex-shrink: 0;
}
.ux-tree-select-node-toggle.leaf {
    visibility: hidden;
}
.ux-tree-select-checkbox {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1rem;
    height: 1rem;
    border: 1px solid #d1d5db;
    border-radius: 0.125rem;
    font-size: 0.625rem;
    color: #fff;
    flex-shrink: 0;
    transition: all 0.15s;
}
.ux-tree-select-checkbox.checked {
    background: #3b82f6;
    border-color: #3b82f6;
}
.ux-tree-select-node-title {
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    cursor: pointer;
}
.ux-tree-select-children {
    overflow: hidden;
    transition: max-height 0.2s ease;
}
.ux-tree-select-children.collapsed {
    max-height: 0;
    overflow: hidden;
}
CSS
        );
    }

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

        $selectorEl = Element::make('div')->class('ux-tree-select-selector');

        // 搜索/显示区域
        if ($this->showSearch) {
            $searchValue = $this->value ? $this->getDisplayText() : '';
            $searchEl = Element::make('input')
                ->attr('type', 'text')
                ->attr('placeholder', $this->placeholder)
                ->attr('value', $searchValue)
                ->class('ux-tree-select-search');
            $selectorEl->child($searchEl);
        } else {
            $displayText = $this->getDisplayText();
            $displayEl = Element::make('span')
                ->class('ux-tree-select-display')
                ->class($displayText === $this->placeholder ? 'placeholder' : '')
                ->attr('data-placeholder', $this->placeholder)
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
            $nodeValue = $node['value'] ?? $node['label'] ?? $node['title'] ?? '';
            $nodeEl = Element::make('div')
                ->class('ux-tree-select-node')
                ->data('node-value', $nodeValue);

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
        $nodeValue = $node['value'] ?? $node['label'] ?? $node['title'] ?? '';
        $values = $this->multiple ? explode(',', $this->value) : [$this->value];
        return in_array($nodeValue, $values);
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
            $nodeValue = $node['value'] ?? $node['label'] ?? $node['title'] ?? '';
            if ($nodeValue === $value) {
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
