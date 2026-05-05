<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\View\Base\Element;

/**
 * 开关
 *
 * 用于布尔值输入，支持开关状态、开/关文字、标签、帮助文本。
 *
 * @ux-category Form
 * @ux-since 1.0.0
 * @ux-example SwitchField::make()->name('notify')->label('接收通知')->checked()
 * @ux-example SwitchField::make()->name('agree')->label('同意条款')->onText('已同意')->offText('未同意')
 * @ux-js-component —
 * @ux-css form.css
 */
class SwitchField extends FormField
{
    protected bool $checked = false;
    protected string $onText = '';
    protected string $offText = '';

    /**
     * 设置选中状态
     * @param bool $checked 是否选中
     * @return static
     * @ux-example SwitchField::make()->checked()
     * @ux-default true
     */
    public function checked(bool $checked = true): static
    {
        $this->checked = $checked;
        return $this;
    }

    /**
     * 设置开启时显示的文字
     * @param string $text 开启文字
     * @return static
     * @ux-example SwitchField::make()->onText('已同意')
     */
    public function onText(string $text): static
    {
        $this->onText = $text;
        return $this;
    }

    /**
     * 设置关闭时显示的文字
     * @param string $text 关闭文字
     * @return static
     * @ux-example SwitchField::make()->offText('未同意')
     */
    public function offText(string $text): static
    {
        $this->offText = $text;
        return $this;
    }

    protected function toElement(): Element
    {
        $groupEl = Element::make('div')->class('ux-form-group');

        $labelEl = $this->renderLabel();
        if ($labelEl) {
            $groupEl->child($labelEl);
        }

        $switchLabelEl = Element::make('label')->class('ux-switch');

        $inputEl = Element::make('input')
            ->attr('type', 'checkbox')
            ->attr('name', $this->name)
            ->attr('value', '1')
            ->class('ux-switch-input');

        if ($this->checked) {
            $inputEl->attr('checked', '');
        }

        foreach ($this->buildFieldAttrs() as $key => $value) {
            if ($key !== 'name') {
                $inputEl->attr($key, $value);
            }
        }

        $switchLabelEl->child($inputEl);
        $switchLabelEl->child(Element::make('span')->class('ux-switch-slider'));
        $switchLabelEl->child(Element::make('span')->class('ux-switch-label-on')->text($this->onText ?: t('ux:switch.on')));
        $switchLabelEl->child(Element::make('span')->class('ux-switch-label-off')->text($this->offText ?: t('ux:switch.off')));

        $groupEl->child($switchLabelEl);

        $helpEl = $this->renderHelp();
        if ($helpEl) {
            $groupEl->child($helpEl);
        }

        return $groupEl;
    }
}
