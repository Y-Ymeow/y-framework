<?php

declare(strict_types=1);

namespace Framework\UX\Form\Components;

use Framework\Component\Live\Attribute\LiveAction;
use Framework\Component\Live\Attribute\State;
use Framework\View\Base\Element;

class LiveTextInput extends BaseField
{
    #[State(frontendEditable: true)]
    public string $inputValue = '';

    public function getType(): string
    {
        return 'text';
    }

    #[LiveAction]
    public function updateValue(array $params = []): void
    {
        $this->inputValue = $params['value'] ?? '';
        $this->value = $this->inputValue;

        if ($this->hasParent()) {
            $this->dispatchToParent('onFieldChange', [
                'field' => $this->name,
                'value' => $this->inputValue,
            ]);
        }
    }

    public function render(): Element
    {
        $wrapper = $this->buildWrapper();
        $wrapper->child($this->buildLabel());

        $input = Element::make('input')
            ->class('ux-form-input', ...$this->extraClasses)
            ->attr('type', 'text')
            ->attr('id', $this->name)
            ->attr('name', $this->name)
            ->attr('data-live-model', $this->name)
            ->attr('data-live-debounce', '300');

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

        $wrapper->child($input);

        $help = $this->buildHelp();
        if ($help) {
            $wrapper->child($help);
        }

        return $wrapper;
    }
}
