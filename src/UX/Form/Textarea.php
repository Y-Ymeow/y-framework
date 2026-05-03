<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\View\Base\Element;

/**
 * 多行文本框
 *
 * 用于多行文本输入，支持行数、标签、验证、禁用、只读、Live 绑定。
 *
 * @ux-category Form
 * @ux-since 1.0.0
 * @ux-example Textarea::make()->name('content')->label('内容')->rows(6)
 * @ux-example Textarea::make()->name('description')->label('描述')->placeholder('请输入描述')->liveModel('description')
 * @ux-js-component —
 * @ux-css form.css
 */
class Textarea extends FormField
{
    protected int $rows = 4;

    /**
     * 设置行数
     * @param int $rows 行数
     * @return static
     * @ux-example Textarea::make()->rows(6)
     * @ux-default 4
     */
    public function rows(int $rows): static
    {
        $this->rows = $rows;
        return $this;
    }

    protected function toElement(): Element
    {
        $groupEl = Element::make('div')->class('ux-form-group');

        $labelEl = $this->renderLabel();
        if ($labelEl) {
            $groupEl->child($labelEl);
        }

        $textareaEl = Element::make('textarea')
            ->class('ux-form-input')
            ->attr('rows', (string)$this->rows);

        foreach ($this->buildFieldAttrs() as $key => $value) {
            $textareaEl->attr($key, $value);
        }

        $textareaEl->text((string)($this->value ?? ''));

        $groupEl->child($textareaEl);

        $helpEl = $this->renderHelp();
        if ($helpEl) {
            $groupEl->child($helpEl);
        }

        return $groupEl;
    }
}
