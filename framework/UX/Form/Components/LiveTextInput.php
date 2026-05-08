<?php

declare(strict_types=1);

namespace Framework\UX\Form\Components;

use Framework\Component\Live\Attribute\State;
use Framework\View\Base\Element;

class LiveTextInput extends BaseField
{
    #[State(frontendEditable: true)]
    public string $inputValue = '';

    protected bool $live = false;

    public function getType(): string
    {
        return 'text';
    }

    /**
     * 启用实时同步模式。默认使用 data-model（本地状态），
     * 调用 live() 后使用 data-live-model.live（同步到服务器）。
     */
    public function live(bool $live = true): static
    {
        $this->live = $live;
        return $this;
    }

    public function onUpdate(): void
    {
        if ($this->inputValue !== '') {
            $this->emit('fieldChanged', [
                'field' => $this->name,
                'value' => $this->inputValue,
            ]);
        }
    }

    public function render(): Element
    {
        $wrapper = $this->buildWrapper();
        $wrapper->child($this->buildLabel());

        $inputRow = Element::make('div')->class('ux-form-input-row');

        $input = Element::make('input')
            ->class('ux-form-input', ...$this->extraClasses)
            ->attr('type', 'text')
            ->attr('id', $this->name)
            ->attr('name', $this->name);

        // live 是可选的：默认 data-model（本地），live() 后 data-live-model.live（同步）
        if ($this->live) {
            $input->attr('data-live-model.live', 'inputValue');
        } else {
            $input->attr('data-model', 'inputValue');
        }

        if ($this->placeholder) {
            $input->attr('placeholder', $this->placeholder);
        }

        if ($this->required) {
            $input->attr('required', '');
        }

        if ($this->disabled) {
            $input->attr('disabled', '');
        }

        $value = $this->inputValue ?: $this->getValue();
        if ($value !== null) {
            $input->attr('value', (string)$value);
        }

        $inputRow->child($input);

        // 渲染 action 按钮
        foreach ($this->fieldActions as $name => $config) {
            $btn = Element::make('button')
                ->class('ux-form-action-btn')
                ->attr('type', 'button')
                ->liveAction($name, $config['event'])
                ->text($config['label'] ?: $name);
            $inputRow->child($btn);
        }

        $wrapper->child($inputRow);

        $help = $this->buildHelp();
        if ($help) {
            $wrapper->child($help);
        }

        return $wrapper;
    }
}
