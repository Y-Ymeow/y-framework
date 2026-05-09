<?php

declare(strict_types=1);

namespace Framework\UX\Form\Components;

use Framework\UX\Form\Contracts\FormField;
use Framework\UX\Form\Concerns\HasMeta;
use Framework\UX\UXLiveComponent;
use Framework\View\Base\Element;

abstract class BaseField extends UXLiveComponent implements FormField
{
    use HasMeta;

    protected string $name;
    protected string|array|null $label = null;
    protected mixed $value = null;
    protected mixed $default = null;
    protected bool $required = false;
    protected bool $disabled = false;
    protected bool $readonly = false;
    protected ?string $placeholder = null;
    protected string|array|null $help = null;
    protected array $rules = [];
    protected array $extraClasses = [];
    protected bool $submitMode = false;
    protected array $fieldActions = [];

    public static function make(...$args): static
    {
        $instance = new static();
        if (!empty($args)) {
            $instance->name = $args[0];
        }
        return $instance;
    }

    public function submitMode(bool $submitMode = true): static
    {
        $this->submitMode = $submitMode;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getLabel(): string|array|null
    {
        return $this->label;
    }

    public function setLabel(string|array $label): static
    {
        $this->label = $label;
        return $this;
    }

    public function label(string|array $label): static
    {
        return $this->setLabel($label);
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function required(bool $required = true): static
    {
        $this->required = $required;
        return $this;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
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

    public function getValue(): mixed
    {
        return $this->value ?? $this->default;
    }

    public function setValue(mixed $value): static
    {
        $this->value = $value;
        return $this;
    }

    public function value(mixed $value): static
    {
        return $this->setValue($value);
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    public function default(mixed $default): static
    {
        $this->default = $default;
        return $this;
    }

    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    public function getHelp(): string|array|null
    {
        return $this->help;
    }

    public function help(string|array $help): static
    {
        $this->help = $help;
        return $this;
    }

    public function getValidationRules(): array
    {
        return $this->rules;
    }

    public function rules(array $rules): static
    {
        $this->rules = $rules;
        return $this;
    }

    public function class(string ...$classes): static
    {
        $this->extraClasses = array_merge($this->extraClasses, $classes);
        return $this;
    }

    /**
     * 为字段注册一个内联 Action。
     * 支持：
     * - 字符串方法名（本组件上的方法）
     * - ['ClassName', 'methodName'] 外部类静态方法引用
     */
    public function action(string $name, string|array $handler, string $label = '', string $event = 'click'): static
    {
        $this->fieldActions[$name] = [
            'handler' => $handler,
            'label' => $label,
            'event' => $event,
        ];

        $this->registerAction($name, $handler, $event);

        return $this;
    }

    protected function resolveLabel(): Element
    {
        if ($this->label === null) {
            return Element::make('span');
        }

        if (is_array($this->label)) {
            $key = $this->label[0] ?? '';
            $params = $this->label[1] ?? [];
            $default = $this->label[2] ?? '';
            return Element::make('span')->intl($key, $params, $default);
        }

        return Element::make('span')->text($this->label);
    }

    protected function buildWrapper(): Element
    {
        return Element::make('div')->class('ux-form-group');
    }

    protected function buildLabel(): Element
    {
        $labelEl = Element::make('label')
            ->class('ux-form-label')
            ->attr('for', $this->name)
            ->child($this->resolveLabel());

        if ($this->required) {
            $labelEl->child(Element::make('span')->class('ux-form-required')->text('*'));
        }

        return $labelEl;
    }

    protected function buildHelp(): ?Element
    {
        if (!$this->help) {
            return null;
        }

        if (is_array($this->help)) {
            $key = $this->help[0] ?? '';
            $params = $this->help[1] ?? [];
            $default = $this->help[2] ?? '';
            return Element::make('span')->class('ux-form-help')->intl($key, $params, $default);
        }

        return Element::make('span')->class('ux-form-help')->text($this->help);
    }
}
