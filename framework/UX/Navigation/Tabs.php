<?php

declare(strict_types=1);

namespace Framework\UX\Navigation;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 标签页
 *
 * 用于展示多标签内容切换，支持线型/胶囊型变体、等宽分布、活体数据绑定。
 *
 * @ux-category Navigation
 * @ux-since 1.0.0
 * @ux-example Tabs::make()->item('标签1', '内容1')->item('标签2', '内容2')
 * @ux-example Tabs::make()->item('首页', $view1)->item('关于', $view2)->pills()->justified()
 * @ux-js-component tabs.js
 * @ux-css tabs.css
 */
class Tabs extends UXComponent
{
    protected static ?string $componentName = 'tabs';

    protected array $items = [];
    protected ?string $activeTab = null;
    protected string $variant = 'line';
    protected bool $justified = false;
    protected ?string $liveModel = null;

    protected function init(): void
    {
        $this->registerJs('tabs', '
            const Tabs = {
                select(tabsId, tabId) {
                    const tabsEl = typeof tabsId === "string" ? document.getElementById(tabsId) : tabsId;
                    if (!tabsEl) return;
                    const link = tabsEl.querySelector(`[data-tab-target="#${tabId}"], [data-ux-tab-select="${tabId}"]`);
                    if (!link) return;
                    tabsEl.querySelectorAll(".ux-tabs-item").forEach(i => i.classList.remove("active"));
                    link.closest(".ux-tabs-item")?.classList.add("active");
                    tabsEl.querySelectorAll(".ux-tabs-pane").forEach(p => p.classList.remove("active", "show"));
                    const pane = tabsEl.querySelector(`#${tabId}`);
                    if (pane) pane.classList.add("active", "show");
                },
                init() {
                    document.addEventListener("click", (e) => {
                        const link = e.target.closest("[data-tab-target], [data-ux-tab-select]");
                        if (!link) return;
                        const tabs = link.closest(".ux-tabs");
                        const targetId = (link.dataset.tabTarget || link.getAttribute("data-ux-tab-select"))?.replace("#", "");
                        if (tabs && targetId) Tabs.select(tabs.id, targetId);
                    });
                }
            };
            return Tabs;
        ');

        $this->registerCss(<<<'CSS'
.ux-tabs {
    font-size: 0.875rem;
}
.ux-tabs-nav {
    display: flex;
    gap: 0;
    list-style: none;
    margin: 0;
    padding: 0;
    border-bottom: 1px solid #e5e7eb;
}
.ux-tabs-pills .ux-tabs-nav {
    border-bottom: none;
    gap: 0.25rem;
    background: #f3f4f6;
    border-radius: 0.5rem;
    padding: 0.25rem;
    display: inline-flex;
}
.ux-tabs-justified .ux-tabs-nav {
    display: flex;
}
.ux-tabs-justified .ux-tabs-item {
    flex: 1;
    text-align: center;
}
.ux-tabs-item {
    margin-bottom: -1px;
}
.ux-tabs-pills .ux-tabs-item {
    margin-bottom: 0;
}
.ux-tabs-link {
    display: block;
    padding: 0.5rem 1rem;
    border: none;
    background: none;
    font-size: 0.875rem;
    font-weight: 500;
    color: #6b7280;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: color 0.15s, border-color 0.15s, background-color 0.15s;
    white-space: nowrap;
}
.ux-tabs-link:hover {
    color: #374151;
}
.ux-tabs-item.active .ux-tabs-link {
    color: #3b82f6;
    border-bottom-color: #3b82f6;
}
.ux-tabs-pills .ux-tabs-link {
    border-bottom: none;
    border-radius: 0.375rem;
}
.ux-tabs-pills .ux-tabs-item.active .ux-tabs-link {
    background: #3b82f6;
    color: #fff;
    border-bottom-color: transparent;
}
.ux-tabs-content {
    padding: 1rem 0;
}
.ux-tabs-pane {
    display: none;
}
.ux-tabs-pane.active {
    display: block;
}
CSS
        );
    }

    /**
     * 添加标签页项
     * @param string $label 标签显示文字
     * @param mixed $content 标签内容（支持字符串或组件）
     * @param string|null $id 标签 ID（自动生成）
     * @param bool $active 是否默认激活
     * @return static
     * @ux-example Tabs::make()->item('首页', $homeView, null, true)
     */
    public function item(string|array $label, mixed $content, ?string $id = null, bool $active = false): static
    {
        $id = $id ?? 'tab-' . count($this->items);
        $this->items[] = [
            'id' => $id,
            'label' => $label,
            'content' => $content,
        ];

        if ($active || $this->activeTab === null) {
            $this->activeTab = $id;
        }

        return $this;
    }

    /**
     * 设置当前激活的标签
     * @param string $id 标签 ID
     * @return static
     */
    public function activeTab(string $id): static
    {
        $this->activeTab = $id;
        return $this;
    }

    /**
     * 设置标签页变体
     * @param string $variant 变体名：line/pills
     * @return static
     * @ux-default 'line'
     */
    public function variant(string $variant): static
    {
        $this->variant = $variant;
        return $this;
    }

    /**
     * 设置 Live 数据绑定
     * @param string $property LiveComponent 属性名
     * @return static
     */
    public function liveModel(string $property): static
    {
        $this->liveModel = $property;
        return $this;
    }

    /**
     * 线型变体（底部横线）
     * @return static
     */
    public function line(): static
    {
        return $this->variant('line');
    }

    /**
     * 胶囊型变体（圆角背景）
     * @return static
     */
    public function pills(): static
    {
        return $this->variant('pills');
    }

    /**
     * 设置等宽分布
     * @param bool $justified 是否等宽
     * @return static
     * @ux-default false
     */
    public function justified(bool $justified = true): static
    {
        $this->justified = $justified;
        return $this;
    }

    /**
     * @ux-internal
     */
    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->id('ux-tabs-' . substr(md5(serialize($this->items)), 0, 8));
        $el->class('ux-tabs');
        $el->class("ux-tabs-{$this->variant}");
        if ($this->justified) {
            $el->class('ux-tabs-justified');
        }

        if ($this->liveModel) {
            $el->data('model', $this->liveModel);
        }

        // Tab list
        $listEl = new Element('ul');
        $listEl->class('ux-tabs-nav');
        $listEl->attr('role', 'tablist');

        foreach ($this->items as $item) {
            $isActive = $this->activeTab === $item['id'];

            $liEl = new Element('li');
            $liEl->class('ux-tabs-item');
            if ($isActive) {
                $liEl->class('active');
            }

            $btnEl = new Element('button');
            $btnEl->class('ux-tabs-link');
            $btnEl->attr('type', 'button');
            $btnEl->attr('data-tab-target', '#' . $item['id']);
            $btnEl->attr('role', 'tab');
            $btnEl->attr('aria-selected', $isActive ? 'true' : 'false');

            if ($this->liveModel) {
                $btnEl->data('model-value', $item['id']);
            }

            if ($this->liveAction) {
                $btnEl->liveAction($this->liveAction, $this->liveEvent ?? 'click');
            }

            if (is_array($item['label'])) {
                $key = $item['label'][0];
                $params = is_array($item['label'][1] ?? null) ? $item['label'][1] : [];
                $default = $item['label'][2] ?? '';
                $btnEl->child(Element::make('span')->intl($key, $params, $default));
            } else {
                $btnEl->text($item['label']);
            }
            $liEl->child($btnEl);
            $listEl->child($liEl);
        }

        $el->child($listEl);

        // Content panes
        $contentEl = new Element('div');
        $contentEl->class('ux-tabs-content');

        foreach ($this->items as $item) {
            $isActive = $this->activeTab === $item['id'];

            $paneEl = new Element('div');
            $paneEl->class('ux-tabs-pane');
            if ($isActive) {
                $paneEl->class('active');
                $paneEl->class('show');
            }
            $paneEl->id($item['id']);
            $paneEl->attr('role', 'tabpanel');

            $content = $item['content'];
            if (is_string($content)) {
                $paneEl->html($content);
            } else {
                $paneEl->child($this->resolveChild($content));
            }

            $contentEl->child($paneEl);
        }

        $el->child($contentEl);

        return $el;
    }
}
