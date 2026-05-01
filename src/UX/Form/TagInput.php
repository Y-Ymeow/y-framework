<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class TagInput extends UXComponent
{
    protected array $value = [];
    protected ?string $placeholder = '请输入标签';
    protected bool $disabled = false;
    protected int $maxCount = 0;
    protected ?string $action = null;
    protected bool $allowClear = true;

    public function value(array $value): static
    {
        $this->value = $value;
        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;
        return $this;
    }

    public function maxCount(int $max): static
    {
        $this->maxCount = $max;
        return $this;
    }

    public function action(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    public function allowClear(bool $allow = true): static
    {
        $this->allowClear = $allow;
        return $this;
    }

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-tag-input');
        if ($this->disabled) {
            $el->class('ux-tag-input-disabled');
        }

        $el->data('tag-value', json_encode($this->value));

        if ($this->maxCount > 0) {
            $el->data('tag-max', (string)$this->maxCount);
        }
        if ($this->action) {
            $el->data('tag-action', $this->action);
        }

        // 标签容器
        $containerEl = Element::make('div')->class('ux-tag-input-container');

        // 已存在的标签
        foreach ($this->value as $tag) {
            $tagEl = Element::make('span')
                ->class('ux-tag-input-tag')
                ->text($tag);

            if (!$this->disabled) {
                $removeEl = Element::make('span')
                    ->class('ux-tag-input-tag-remove')
                    ->html('<i class="bi bi-x"></i>');
                $tagEl->child($removeEl);
            }

            $containerEl->child($tagEl);
        }

        // 输入框
        if (!$this->disabled && ($this->maxCount === 0 || count($this->value) < $this->maxCount)) {
            $inputEl = Element::make('input')
                ->attr('type', 'text')
                ->attr('placeholder', $this->placeholder)
                ->class('ux-tag-input-field');
            $containerEl->child($inputEl);
        }

        $el->child($containerEl);

        // 隐藏的真实输入（用于表单提交）
        $hiddenEl = Element::make('input')
            ->attr('type', 'hidden')
            ->attr('name', $this->id ?? '')
            ->attr('value', implode(',', $this->value))
            ->class('ux-tag-input-hidden');
        $el->child($hiddenEl);

        // Live 桥接隐藏 input
        $liveInput = $this->createLiveModelInput(implode(',', $this->value));
        if ($liveInput) {
            $el->child($liveInput);
        }

        return $el;
    }
}
