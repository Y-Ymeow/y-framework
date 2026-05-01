<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class ColorPicker extends UXComponent
{
    protected ?string $value = null;
    protected bool $allowClear = false;
    protected bool $disabled = false;
    protected bool $showText = true;
    protected ?string $action = null;
    protected array $presets = [];

    public function value(string $value): static
    {
        $this->value = $value;
        return $this;
    }

    public function allowClear(bool $allow = true): static
    {
        $this->allowClear = $allow;
        return $this;
    }

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;
        return $this;
    }

    public function showText(bool $show = true): static
    {
        $this->showText = $show;
        return $this;
    }

    public function action(string $action): static
    {
        $this->action = $action;
        return $this;
    }

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
