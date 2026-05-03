<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

abstract class FormField extends UXComponent
{
    protected string $name = '';
    protected string $label = '';
    protected bool $required = false;
    protected mixed $value = null;
    protected string $placeholder = '';
    protected string $help = '';
    protected bool $disabled = false;
    protected bool $readonly = false;
    protected string $autocomplete = '';
    protected array $rules = [];
    protected ?string $liveModel = null;
    protected ?string $error = null;
    protected bool $invalid = false;

    public function name(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function label(string $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function required(bool $required = true): static
    {
        $this->required = $required;
        return $this;
    }

    public function value(mixed $value): static
    {
        $this->value = $value;
        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    public function help(string $help): static
    {
        $this->help = $help;
        return $this;
    }

    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;
        return $this;
    }

    public function readonly(bool $readonly = true): static
    {
        $this->readonly = $readonly;
        return $this;
    }

    public function autocomplete(string $autocomplete): static
    {
        $this->autocomplete = $autocomplete;
        return $this;
    }

    public function rules(array $rules): static
    {
        $this->rules = $rules;
        return $this;
    }

    public function liveModel(string $property): static
    {
        $this->liveModel = $property;
        return $this;
    }

    /**
     * 设置验证错误信息
     *
     * @param string|null $message 错误信息，传 null 则清除错误
     */
    public function error(?string $message = null): static
    {
        $this->error = $message;
        $this->invalid = $message !== null;
        return $this;
    }

    /**
     * 设置无效状态（不显示错误信息）
     */
    public function invalid(bool $invalid = true): static
    {
        $this->invalid = $invalid;
        return $this;
    }

    /**
     * 获取错误信息
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * 是否处于无效状态
     */
    public function isInvalid(): bool
    {
        return $this->invalid;
    }

    protected function buildFieldAttrs(): array
    {
        $attrs = [];
        $attrs['id'] = $this->name;
        $attrs['name'] = $this->name;

        if ($this->placeholder) {
            $attrs['placeholder'] = $this->placeholder;
        }

        if ($this->required) {
            $attrs['required'] = 'required';
        }

        if ($this->disabled) {
            $attrs['disabled'] = 'disabled';
        }

        if ($this->readonly) {
            $attrs['readonly'] = 'readonly';
        }

        if ($this->autocomplete) {
            $attrs['autocomplete'] = $this->autocomplete;
        }

        if ($this->liveModel) {
            $attrs['data-live-model'] = $this->liveModel;
        }

        if ($this->invalid) {
            $attrs['data-invalid'] = 'true';
            $attrs['class'] = (isset($attrs['class']) ? $attrs['class'] . ' ' : '') . 'ux-field-invalid';
        }

        return $attrs;
    }

    protected function renderLabel(): ?Element
    {
        if (!$this->label) return null;

        $labelEl = Element::make('label')
            ->class('ux-form-label')
            ->attr('for', $this->name)
            ->text($this->label);

        if ($this->required) {
            $labelEl->child(Element::make('span')->class('ux-form-required')->text('*'));
        }

        return $labelEl;
    }

    protected function renderHelp(): ?Element
    {
        if (!$this->help) return null;

        return Element::make('div')->class('ux-form-help')->html($this->help);
    }

    /**
     * 渲染错误信息
     */
    protected function renderError(): ?Element
    {
        if (!$this->error) return null;

        return Element::make('div')
            ->class('ux-form-error')
            ->text($this->error);
    }

    public function getName(): string
    {
        return $this->name;
    }
}
