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
    protected static ?string $componentName = 'transfer';

    protected array $dataSource = [];
    protected array $targetKeys = [];
    protected ?string $titles = null;
    protected bool $disabled = false;
    protected bool $showSearch = false;
    protected ?string $action = null;
    protected ?string $searchPlaceholder = '请输入搜索内容';

    protected function init(): void
    {
        $this->registerJs('transfer', '
            const Transfer = {
                init() {
                    // 初始化所有穿梭框：更新按钮状态和计数
                    document.querySelectorAll(".ux-transfer").forEach(transfer => {
                        this.updatePanelState(transfer);
                    });

                    document.addEventListener("click", (e) => {
                        if (!e.target || !e.target.closest) return;

                        // 选择项点击
                        const item = e.target.closest(".ux-transfer-item");
                        if (item && !item.classList.contains("ux-transfer-item-disabled")) {
                            const checkbox = item.querySelector("input[type=\"checkbox\"]");
                            if (checkbox && !checkbox.disabled) {
                                item.classList.toggle("selected");
                                checkbox.checked = item.classList.contains("selected");
                                const transfer = item.closest(".ux-transfer");
                                this.updatePanelState(transfer);
                            }
                        }

                        // 全选/取消全选
                        const checkAll = e.target.closest(".ux-transfer-panel-check-all");
                        if (checkAll) {
                            const panel = checkAll.closest(".ux-transfer-panel");
                            const transfer = checkAll.closest(".ux-transfer");
                            const isChecked = checkAll.checked;
                            panel.querySelectorAll(".ux-transfer-item:not(.ux-transfer-item-disabled)").forEach(it => {
                                const cb = it.querySelector("input[type=\"checkbox\"]");
                                if (cb && !cb.disabled) {
                                    it.classList.toggle("selected", isChecked);
                                    cb.checked = isChecked;
                                }
                            });
                            this.updatePanelState(transfer);
                        }

                        // 左移按钮
                        const leftBtn = e.target.closest(".ux-transfer-btn-left");
                        if (leftBtn && !leftBtn.disabled) {
                            const transfer = leftBtn.closest(".ux-transfer");
                            if (transfer) this.moveLeft(transfer);
                        }

                        // 右移按钮
                        const rightBtn = e.target.closest(".ux-transfer-btn-right");
                        if (rightBtn && !rightBtn.disabled) {
                            const transfer = rightBtn.closest(".ux-transfer");
                            if (transfer) this.moveRight(transfer);
                        }
                    });

                    // 搜索功能
                    document.addEventListener("input", (e) => {
                        if (!e.target || !e.target.classList.contains("ux-transfer-panel-search-input")) return;
                        const searchInput = e.target;
                        const panel = searchInput.closest(".ux-transfer-panel");
                        const keyword = searchInput.value.toLowerCase();
                        panel.querySelectorAll(".ux-transfer-item").forEach(item => {
                            const label = item.querySelector(".ux-transfer-item-label");
                            if (label) {
                                const text = label.textContent.toLowerCase();
                                item.style.display = text.includes(keyword) ? "" : "none";
                            }
                        });
                    });
                },
                moveRight(transfer) {
                    const panels = transfer.querySelectorAll(".ux-transfer-panel");
                    const leftPanel = panels[0];
                    const rightPanel = panels[1];
                    const selected = leftPanel.querySelectorAll(".ux-transfer-item.selected");
                    if (selected.length === 0) return;
                    const rightList = rightPanel.querySelector(".ux-transfer-panel-list");
                    selected.forEach(item => {
                        item.classList.remove("selected");
                        item.dataset.side = "right";
                        const checkbox = item.querySelector("input[type=\"checkbox\"]");
                        if (checkbox) checkbox.checked = false;
                        rightList.appendChild(item);
                    });
                    const rightEmpty = rightList.querySelector(".ux-transfer-panel-empty");
                    if (rightEmpty) rightEmpty.remove();
                    this.updatePanelState(transfer);
                    this.syncToServer(transfer);
                },
                moveLeft(transfer) {
                    const panels = transfer.querySelectorAll(".ux-transfer-panel");
                    const leftPanel = panels[0];
                    const rightPanel = panels[1];
                    const selected = rightPanel.querySelectorAll(".ux-transfer-item.selected");
                    if (selected.length === 0) return;
                    const leftList = leftPanel.querySelector(".ux-transfer-panel-list");
                    selected.forEach(item => {
                        item.classList.remove("selected");
                        item.dataset.side = "left";
                        const checkbox = item.querySelector("input[type=\"checkbox\"]");
                        if (checkbox) checkbox.checked = false;
                        leftList.appendChild(item);
                    });
                    const leftEmpty = leftList.querySelector(".ux-transfer-panel-empty");
                    if (leftEmpty) leftEmpty.remove();
                    this.updatePanelState(transfer);
                    this.syncToServer(transfer);
                },
                updatePanelState(transfer) {
                    const panels = transfer.querySelectorAll(".ux-transfer-panel");
                    const leftPanel = panels[0];
                    const rightPanel = panels[1];
                    const rightBtn = transfer.querySelector(".ux-transfer-btn-right");
                    const leftBtn = transfer.querySelector(".ux-transfer-btn-left");
                    if (rightBtn) rightBtn.disabled = leftPanel.querySelectorAll(".ux-transfer-item.selected").length === 0;
                    if (leftBtn) leftBtn.disabled = rightPanel.querySelectorAll(".ux-transfer-item.selected").length === 0;
                    this.updateCount(transfer);
                    this.updateCheckAllState(leftPanel);
                    this.updateCheckAllState(rightPanel);
                },
                updateCheckAllState(panel) {
                    const checkAll = panel.querySelector(".ux-transfer-panel-check-all");
                    if (!checkAll) return;
                    const items = panel.querySelectorAll(".ux-transfer-item:not(.ux-transfer-item-disabled)");
                    const selectedItems = panel.querySelectorAll(".ux-transfer-item.selected:not(.ux-transfer-item-disabled)");
                    if (items.length === 0) {
                        checkAll.checked = false;
                        checkAll.indeterminate = false;
                    } else if (selectedItems.length === items.length) {
                        checkAll.checked = true;
                        checkAll.indeterminate = false;
                    } else if (selectedItems.length > 0) {
                        checkAll.checked = false;
                        checkAll.indeterminate = true;
                    } else {
                        checkAll.checked = false;
                        checkAll.indeterminate = false;
                    }
                },
                updateCount(transfer) {
                    const panels = transfer.querySelectorAll(".ux-transfer-panel");
                    panels.forEach(panel => {
                        const items = panel.querySelectorAll(".ux-transfer-item");
                        const countEl = panel.querySelector(".ux-transfer-panel-count");
                        if (countEl) countEl.textContent = items.length;
                    });
                },
                syncToServer(transfer) {
                    const panels = transfer.querySelectorAll(".ux-transfer-panel");
                    const rightPanel = panels[1];
                    const targetKeys = [];
                    rightPanel.querySelectorAll(".ux-transfer-item").forEach(item => {
                        const key = item.dataset.key;
                        if (key) targetKeys.push(key);
                    });
                    transfer.dispatchEvent(new CustomEvent("ux:change", {
                        detail: { value: JSON.stringify(targetKeys) },
                        bubbles: true
                    }));
                },
                getValue(transfer) {
                    const panels = transfer.querySelectorAll(".ux-transfer-panel");
                    const rightPanel = panels[1];
                    return Array.from(rightPanel.querySelectorAll(".ux-transfer-item")).map(item => item.dataset.key);
                }
            };
            return Transfer;
        ');

        $this->registerCss(<<<'CSS'
.ux-transfer {
    display: flex;
    align-items: stretch;
    gap: 0.5rem;
}
.ux-transfer-disabled {
    opacity: 0.5;
    pointer-events: none;
}
.ux-transfer-panel {
    flex: 1;
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    display: flex;
    flex-direction: column;
    min-width: 14rem;
    max-height: 20rem;
    overflow: hidden;
}
.ux-transfer-panel-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    border-bottom: 1px solid #f3f4f6;
    background: #f9fafb;
    font-size: 0.8125rem;
}
.ux-transfer-panel-checkbox {
    display: inline-flex;
    align-items: center;
    cursor: pointer;
}
.ux-transfer-panel-title {
    font-weight: 500;
    color: #374151;
}
.ux-transfer-panel-count {
    margin-left: auto;
    color: #9ca3af;
}
.ux-transfer-panel-search {
    position: relative;
    padding: 0.375rem 0.5rem;
    border-bottom: 1px solid #f3f4f6;
}
.ux-transfer-panel-search-input {
    width: 100%;
    padding: 0.25rem 0.5rem 0.25rem 1.75rem;
    border: 1px solid #e5e7eb;
    border-radius: 0.25rem;
    font-size: 0.8125rem;
    outline: none;
    transition: border-color 0.15s;
}
.ux-transfer-panel-search-input:focus {
    border-color: #3b82f6;
}
.ux-transfer-panel-search-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: #9ca3af;
    font-size: 0.75rem;
}
.ux-transfer-panel-list {
    flex: 1;
    overflow-y: auto;
    padding: 0.25rem 0;
}
.ux-transfer-panel-empty {
    padding: 2rem;
    text-align: center;
    color: #9ca3af;
    font-size: 0.8125rem;
}
.ux-transfer-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.375rem 0.75rem;
    cursor: pointer;
    font-size: 0.8125rem;
    color: #374151;
    transition: background-color 0.1s;
}
.ux-transfer-item:hover {
    background: #f9fafb;
}
.ux-transfer-item.selected {
    background: #eff6ff;
}
.ux-transfer-item-disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
.ux-transfer-item-checkbox {
    display: inline-flex;
    align-items: center;
    cursor: pointer;
}
.ux-transfer-item-label {
    flex: 1;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}
.ux-transfer-panel-footer {
    border-top: 1px solid #f3f4f6;
    padding: 0.375rem 0.75rem;
    background: #f9fafb;
}
.ux-transfer-operations {
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 0.25rem;
}
.ux-transfer-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    background: #fff;
    color: #6b7280;
    cursor: pointer;
    transition: all 0.15s;
}
.ux-transfer-btn:hover:not(:disabled) {
    border-color: #3b82f6;
    color: #3b82f6;
}
.ux-transfer-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}
CSS
        );
    }

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
