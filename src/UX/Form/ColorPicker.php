<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 颜色选择器
 *
 * 用于颜色选择，支持颜色值显示、清除、禁用、预设颜色、Live 绑定。
 *
 * @ux-category Form
 * @ux-since 1.0.0
 * @ux-example ColorPicker::make()->value('#3b82f6')->label('主题色')
 * @ux-example ColorPicker::make()->value('#ef4444')->presets(['#ef4444', '#3b82f6', '#10b981'])
 * @ux-js-component color-picker.js
 * @ux-css color-picker.css
 */
class ColorPicker extends UXComponent
{
    protected ?string $value = null;
    protected bool $allowClear = false;
    protected bool $disabled = false;
    protected bool $showText = true;
    protected ?string $action = null;
    protected array $presets = [];

    /**
     * 设置颜色值
     * @param string $value 颜色值（如 #3b82f6）
     * @return static
     * @ux-example ColorPicker::make()->value('#ef4444')
     */
    public function value(string $value): static
    {
        $this->value = $value;
        return $this;
    }

    /**
     * 启用清除按钮
     * @param bool $allow 是否允许清除
     * @return static
     * @ux-example ColorPicker::make()->allowClear()
     * @ux-default true
     */
    public function allowClear(bool $allow = true): static
    {
        $this->allowClear = $allow;
        return $this;
    }

    /**
     * 设置禁用状态
     * @param bool $disabled 是否禁用
     * @return static
     * @ux-example ColorPicker::make()->disabled()
     * @ux-default true
     */
    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;
        return $this;
    }

    /**
     * 显示颜色值文本
     * @param bool $show 是否显示
     * @return static
     * @ux-example ColorPicker::make()->showText(false)
     * @ux-default true
     */
    public function showText(bool $show = true): static
    {
        $this->showText = $show;
        return $this;
    }

    /**
     * 设置 LiveAction（选择颜色时触发）
     * @param string $action Action 名称
     * @return static
     * @ux-example ColorPicker::make()->action('updateColor')
     */
    public function action(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    /**
     * 设置预设颜色列表
     * @param array $presets 预设颜色数组
     * @return static
     * @ux-example ColorPicker::make()->presets(['#ef4444', '#3b82f6', '#10b981'])
     */
    public function presets(array $presets): static
    {
        $this->presets = $presets;
        return $this;
    }

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-color-picker');
        if ($this->disabled) {
            $el->class('ux-color-picker-disabled');
        }

        $el->data('color-value', $this->value ?? '#3b82f6');

        if ($this->action) {
            $el->data('color-action', $this->action);
        }

        if (!empty($this->presets)) {
            $el->data('color-presets', json_encode($this->presets));
        }

        // 颜色预览区域
        $previewEl = Element::make('div')->class('ux-color-picker-preview');
        $colorBlock = Element::make('div')
            ->class('ux-color-picker-block')
            ->style('background-color: ' . ($this->value ?? '#3b82f6'));
        $previewEl->child($colorBlock);

        if ($this->showText) {
            $textEl = Element::make('span')
                ->class('ux-color-picker-text')
                ->text($this->value ?? '#3b82f6');
            $previewEl->child($textEl);
        }

        if ($this->allowClear && $this->value) {
            $clearEl = Element::make('span')
                ->class('ux-color-picker-clear')
                ->html('<i class="bi bi-x"></i>');
            $previewEl->child($clearEl);
        }

        $el->child($previewEl);

        // 隐藏的原生 input
        $inputEl = Element::make('input')
            ->attr('type', 'color')
            ->attr('value', $this->value ?? '#3b82f6')
            ->class('ux-color-picker-input');
        $el->child($inputEl);

        // Live 桥接隐藏 input
        $liveInput = $this->createLiveModelInput($this->value ?? '#3b82f6');
        if ($liveInput) {
            $el->child($liveInput);
        }

        return $el;
    }
}
