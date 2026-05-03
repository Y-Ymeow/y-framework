<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\View\Base\Element;

/**
 * 单选框
 *
 * 用于从多个选项中选择一个值，支持选项列表和内联布局。
 *
 * @ux-category Form
 * @ux-since 1.0.0
 * @ux-example Radio::make()->label('性别')->options(['male' => '男', 'female' => '女'])->model('gender')
 * @ux-example Radio::make()->label('支付方式')->options(['alipay' => '支付宝', 'wechat' => '微信'])->inline()
 * @ux-js-component —
 * @ux-css form.css
 */
class Radio extends FormField
{
    protected array $options = [];
    protected bool $inline = false;

    /**
     * 设置选项列表
     * @param array $options 选项数组 ['value' => '标签']
     * @return static
     * @ux-example Radio::make()->options(['male' => '男', 'female' => '女'])
     */
    public function options(array $options): static
    {
        $this->options = $options;
        return $this;
    }

    /**
     * 设置内联布局
     * @param bool $inline 是否内联
     * @return static
     * @ux-example Radio::make()->inline()
     * @ux-default true
     */
    public function inline(bool $inline = true): static
    {
        $this->inline = $inline;
        return $this;
    }

    protected function toElement(): Element
    {
        $groupEl = Element::make('div')->class('ux-form-group');

        $labelEl = $this->renderLabel();
        if ($labelEl) {
            $groupEl->child($labelEl);
        }

        $wrapperEl = Element::make('div');
        $wrapperEl->class('ux-form-radio-group');
        if ($this->inline) {
            $wrapperEl->class('ux-form-radio-inline');
        }

        foreach ($this->options as $value => $label) {
            $radioLabelEl = Element::make('label')->class('ux-form-radio');

            $inputEl = Element::make('input')
                ->attr('type', 'radio')
                ->attr('name', $this->name)
                ->attr('value', (string)$value)
                ->class('ux-form-input');

            if ($this->value !== null && (string)$this->value === (string)$value) {
                $inputEl->attr('checked', '');
            }

            $radioLabelEl->child($inputEl);
            $radioLabelEl->child(Element::make('span')->text($label));

            $wrapperEl->child($radioLabelEl);
        }

        $groupEl->child($wrapperEl);

        $helpEl = $this->renderHelp();
        if ($helpEl) {
            $groupEl->child($helpEl);
        }

        return $groupEl;
    }
}
