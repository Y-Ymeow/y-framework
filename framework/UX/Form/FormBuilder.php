<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\UX\Form\Contracts\FormComponent;
use Framework\UX\Form\Concerns\HasComponents;
use Framework\UX\Form\Components\TextInput;
use Framework\UX\Form\Components\Textarea;
use Framework\UX\Form\Components\Select;
use Framework\UX\Form\Components\Checkbox;
use Framework\UX\Form\Components\RadioGroup;
use Framework\UX\Form\RichEditor;
use Framework\UX\UI\Button;
use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class FormBuilder extends UXComponent
{
    use HasComponents;

    protected string $method = 'POST';
    protected string $action = '';
    protected bool $multipart = false;
    protected ?string $submitLabel = null;
    protected ?string $liveSubmitAction = null;
    protected array $data = [];
    protected array $liveBind = [];
    protected bool $submitMode = false;

    protected static array $macros = [];
    protected static array $registeredComponents = [];

    public static function make(): static
    {
        return new static();
    }

    public function submitMode(bool $submitMode = true): static
    {
        $this->submitMode = $submitMode;
        return $this;
    }

    public function method(string $method): static
    {
        $this->method = strtoupper($method);
        return $this;
    }

    public function get(): static
    {
        return $this->method('GET');
    }

    public function post(): static
    {
        return $this->method('POST');
    }

    public function put(): static
    {
        return $this->method('PUT');
    }

    public function delete(): static
    {
        return $this->method('DELETE');
    }

    public function action(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    public function multipart(bool $multipart = true): static
    {
        $this->multipart = $multipart;
        return $this;
    }

    public function submitLabel(string $label): static
    {
        $this->submitLabel = $label;
        return $this;
    }

    public function liveSubmit(string $action, ?string $label = null): static
    {
        $this->submitMode = true;
        $this->liveSubmitAction = $action;
        if ($label !== null) {
            $this->submitLabel = $label;
        }
        return $this;
    }

    public function add(FormComponent|UXComponent|Element $component): static
    {
        if ($this->submitMode && method_exists($component, 'submitMode')) {
            $component->submitMode(true);
        }

        $this->components[] = $component;
        return $this;
    }

    public function fields(array $components): static
    {
        foreach ($components as $component) {
            if ($component instanceof FormComponent || $component instanceof UXComponent || $component instanceof Element) {
                $this->add($component);
            }
        }
        return $this;
    }

    public function fill(array $data): static
    {
        $this->data = $data;
        $this->fillComponents($this->components, $data);
        return $this;
    }

    public function liveBind(string $field, string $property): static
    {
        $this->liveBind[$field] = $property;
        return $this;
    }

    public function getDefaults(): array
    {
        $defaults = [];
        $this->extractDefaults($this->components, $defaults);
        return $defaults;
    }

    public function getFields(): array
    {
        $fields = [];
        $this->extractFields($this->components, $fields);
        return $fields;
    }

    public static function macro(string $name, callable $callback): void
    {
        static::$macros[$name] = $callback;
    }

    public function __call(string $name, array $arguments)
    {
        if (isset(static::$macros[$name])) {
            return \Closure::fromCallable(static::$macros[$name])->call($this, ...$arguments);
        }

        throw new \BadMethodCallException("Method {$name} does not exist on " . static::class);
    }

    public static function registerComponent(string $alias, string $class): void
    {
        static::$registeredComponents[$alias] = $class;
    }

    protected function fillComponents(array $components, array $data): void
    {
        foreach ($components as $component) {
            if ($component instanceof FormComponent) {
                $name = $component->getName();
                if ($name && isset($data[$name])) {
                    $component->setValue($data[$name]);
                }
                if ($this->submitMode && method_exists($component, 'submitMode')) {
                    $component->submitMode(true);
                }
            }

            if (method_exists($component, 'getComponents')) {
                $this->fillComponents($component->getComponents(), $data);
            }
        }
    }

    protected function extractDefaults(array $components, array &$defaults): void
    {
        foreach ($components as $component) {
            if ($component instanceof FormComponent) {
                $name = $component->getName();
                $default = $component->getDefault();
                if ($name && $default !== null) {
                    $defaults[$name] = $default;
                }
            }

            if (method_exists($component, 'getComponents')) {
                $this->extractDefaults($component->getComponents(), $defaults);
            }
        }
    }

    protected function extractFields(array $components, array &$fields): void
    {
        foreach ($components as $component) {
            if ($component instanceof FormComponent && $component->getName()) {
                $fields[$component->getName()] = $component;
            }

            if (method_exists($component, 'getComponents')) {
                $this->extractFields($component->getComponents(), $fields);
            }
        }
    }

    protected function toElement(): Element
    {
        $form = Element::make('form')
            ->attr('method', $this->method);

        if ($this->action) {
            $form->attr('action', $this->action);
        }

        if ($this->multipart) {
            $form->attr('enctype', 'multipart/form-data');
        }

        if ($this->liveSubmitAction) {
            $form->attr('data-submit:submit.prevent', $this->liveSubmitAction);
        }

        foreach ($this->renderComponents() as $element) {
            $form->child($element);
        }

        if ($this->submitLabel) {
            $form->child(
                Element::make('div')
                    ->class('ux-form-actions')
                    ->child(
                        Button::make()
                            ->label($this->submitLabel)
                            ->submit()
                            ->primary()
                    )
            );
        }

        return $form;
    }

    public function text(string $name, string|array $label = '', array $options = []): static
    {
        $input = TextInput::make($name);
        
        if ($label) {
            $input->label($label);
        }
        
        if (isset($options['placeholder'])) {
            $input->placeholder($options['placeholder']);
        }
        
        if (isset($options['required']) && $options['required']) {
            $input->required();
        }
        
        if (isset($options['disabled']) && $options['disabled']) {
            $input->disabled();
        }
        
        if (isset($options['default'])) {
            $input->default($options['default']);
        }
        
        if ($this->submitMode) {
            $input->submitMode(true);
        }

        return $this->add($this->applyFieldOptions($input, $options));
    }

    public function email(string $name, string|array $label = '', array $options = []): static
    {
        $input = TextInput::make($name)->email();
        
        if ($label) {
            $input->label($label);
        }
        
        if (isset($options['placeholder'])) {
            $input->placeholder($options['placeholder']);
        }
        
        if (isset($options['required']) && $options['required']) {
            $input->required();
        }
        
        return $this->add($this->applyFieldOptions($input, $options));
    }

    public function password(string $name, string|array $label = '', array $options = []): static
    {
        $input = TextInput::make($name)->password();
        
        if ($label) {
            $input->label($label);
        }
        
        if (isset($options['required']) && $options['required']) {
            $input->required();
        }
        
        return $this->add($this->applyFieldOptions($input, $options));
    }

    public function number(string $name, string|array $label = '', array $options = []): static
    {
        $input = TextInput::make($name)->number();
        
        if ($label) {
            $input->label($label);
        }
        
        if (isset($options['min'])) {
            $input->withMeta('min', $options['min']);
        }
        
        if (isset($options['max'])) {
            $input->withMeta('max', $options['max']);
        }
        
        if (isset($options['step'])) {
            $input->withMeta('step', $options['step']);
        }
        
        return $this->add($this->applyFieldOptions($input, $options));
    }

    public function textarea(string $name, string|array $label = '', array $options = []): static
    {
        $textarea = Textarea::make($name);
        
        if ($label) {
            $textarea->label($label);
        }
        
        if (isset($options['rows'])) {
            $textarea->rows($options['rows']);
        }
        
        if (isset($options['placeholder'])) {
            $textarea->placeholder($options['placeholder']);
        }
        
        if (isset($options['required']) && $options['required']) {
            $textarea->required();
        }
        
        return $this->add($this->applyFieldOptions($textarea, $options));
    }

    public function select(string $name, string|array $label = '', array $options = [], array $selectOptions = []): static
    {
        $select = Select::make($name);
        
        if ($label) {
            $select->label($label);
        }
        
        if (!empty($selectOptions)) {
            $select->options($selectOptions);
        }
        
        if (isset($options['required']) && $options['required']) {
            $select->required();
        }
        
        if (isset($options['multiple']) && $options['multiple']) {
            $select->multiple();
        }
        
        if (isset($options['default'])) {
            $select->default($options['default']);
        }
        
        return $this->add($this->applyFieldOptions($select, $options));
    }

    public function checkbox(string $name, string|array $label = '', array $options = []): static
    {
        $checkbox = Checkbox::make($name);
        
        if ($label) {
            $checkbox->label($label);
        }
        
        if (isset($options['default'])) {
            $checkbox->default($options['default']);
        }
        
        return $this->add($this->applyFieldOptions($checkbox, $options));
    }

    public function radio(string $name, string|array $label = '', array $choices = [], array $options = []): static
    {
        $radio = RadioGroup::make($name);
        
        if ($label) {
            $radio->label($label);
        }
        
        if (!empty($choices)) {
            $radio->options($choices);
        }
        
        if (isset($options['inline']) && $options['inline']) {
            $radio->inline();
        }
        
        if (isset($options['default'])) {
            $radio->default($options['default']);
        }
        
        return $this->add($this->applyFieldOptions($radio, $options));
    }

    public function hidden(string $name, string $value = ''): static
    {
        $input = TextInput::make($name)->inputType('hidden');
        $input->setValue($value);
        return $this->add($input);
    }

    public function file(string $name, string|array $label = '', array $options = []): static
    {
        $this->multipart = true;
        $input = TextInput::make($name)->inputType('file');
        
        if ($label) {
            $input->label($label);
        }
        
        if (isset($options['accept'])) {
            $input->withMeta('accept', $options['accept']);
        }
        
        if (isset($options['multiple']) && $options['multiple']) {
            $input->withMeta('multiple', true);
        }
        
        return $this->add($this->applyFieldOptions($input, $options));
    }

    public function richEditor(string $name, mixed $label = '', array $options = []): static
    {
        $editor = new RichEditor($name);
        
        if ($label) {
            $editor->label($label);
        }
        
        if (isset($options['placeholder'])) {
            $editor->placeholder($options['placeholder']);
        }

        if (isset($options['height'])) {
            $editor->minHeight($options['height']);
        }
        
        return $this->add($this->applyFieldOptions($editor, $options));
    }

    public function blockEditor(string $name, mixed $label = '', array $options = []): static
    {
        return $this->richEditor($name, $label, $options);
    }

    protected function applyFieldOptions(object $field, array $options): object
    {
        foreach (['placeholder', 'help', 'default', 'value'] as $method) {
            if (array_key_exists($method, $options) && method_exists($field, $method)) {
                $field->{$method}($options[$method]);
            }
        }

        foreach (['required', 'disabled', 'readonly'] as $method) {
            if (!empty($options[$method]) && method_exists($field, $method)) {
                $field->{$method}();
            }
        }

        if (!empty($options['class']) && method_exists($field, 'class')) {
            foreach ((array) $options['class'] as $class) {
                $field->class((string) $class);
            }
        }

        if ($this->submitMode && method_exists($field, 'submitMode')) {
            $field->submitMode(true);
        }

        return $field;
    }
}
