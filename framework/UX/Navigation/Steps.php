<?php

declare(strict_types=1);

namespace Framework\UX\Navigation;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 步骤条
 *
 * 用于展示多步骤流程，支持步骤标题、描述、当前步骤、垂直/水平布局。
 *
 * @ux-category Navigation
 * @ux-since 1.0.0
 * @ux-example Steps::make()->item('步骤1')->item('步骤2')->item('步骤3')->current(2)
 * @ux-example Steps::make()->item('开始', '初始化')->item('处理', '运行中')->item('完成', '已结束')->vertical()
 * @ux-js-component —
 * @ux-css steps.css
 */
class Steps extends UXComponent
{
    protected array $items = [];
    protected int $current = 0;
    protected string $direction = 'horizontal';

    /**
     * 添加步骤项
     * @param string $title 步骤标题
     * @param string|null $description 步骤描述（可选）
     * @return static
     * @ux-example Steps::make()->item('注册', '填写基本信息')
     */
    public function item(string $title, ?string $description = null): static
    {
        $this->items[] = [
            'title' => $title,
            'description' => $description,
        ];
        return $this;
    }

    /**
     * 设置当前步骤（从 0 开始）
     * @param int $current 当前步骤索引
     * @return static
     * @ux-default 0
     */
    public function current(int $current): static
    {
        $this->current = $current;
        return $this;
    }

    /**
     * 设置为垂直布局
     * @return static
     */
    public function vertical(): static
    {
        $this->direction = 'vertical';
        return $this;
    }

    /**
     * @ux-internal
     */
    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-steps');
        $el->class("ux-steps-{$this->direction}");

        foreach ($this->items as $index => $item) {
            $status = $index < $this->current ? 'finish' : ($index === $this->current ? 'process' : 'wait');

            $itemEl = new Element('div');
            $itemEl->class('ux-steps-item');
            $itemEl->class("ux-steps-item-{$status}");

            // Icon
            $iconEl = new Element('div');
            $iconEl->class('ux-steps-item-icon');
            if ($status === 'finish') {
                $iconEl->html('✓');
            } else {
                $iconEl->text((string)($index + 1));
            }

            // Content
            $contentEl = new Element('div');
            $contentEl->class('ux-steps-item-content');

            $titleEl = new Element('div');
            $titleEl->class('ux-steps-item-title');
            $titleEl->text($item['title']);
            $contentEl->child($titleEl);

            if ($item['description']) {
                $descEl = new Element('div');
                $descEl->class('ux-steps-item-description');
                $descEl->text($item['description']);
                $contentEl->child($descEl);
            }

            // Container
            $containerEl = new Element('div');
            $containerEl->class('ux-steps-item-container');
            $containerEl->child($iconEl);
            $containerEl->child($contentEl);

            // Tail (except last item)
            if ($index < count($this->items) - 1) {
                $tailEl = new Element('div');
                $tailEl->class('ux-steps-item-tail');
                $containerEl->child($tailEl);
            }

            $itemEl->child($containerEl);
            $el->child($itemEl);
        }

        return $el;
    }
}
