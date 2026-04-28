<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\UX\UI\Button;
use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class FormBuilder extends UXComponent
{
    protected string $method = 'POST';
    protected string $action = '';
    protected array $fields = [];
    protected bool $multipart = false;
    protected ?string $submitLabel = null;
    protected array $liveBind = [];

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

    public function text(string $name, string $label, array $options = []): static
    {
        $this->fields[] = array_merge([
            'type' => 'text',
            'name' => $name,
            'label' => $label,
        ], $options);
        return $this;
    }

    public function email(string $name, string $label, array $options = []): static
    {
        $this->fields[] = array_merge([
            'type' => 'email',
            'name' => $name,
            'label' => $label,
        ], $options);
        return $this;
    }

    public function password(string $name, string $label, array $options = []): static
    {
        $this->fields[] = array_merge([
            'type' => 'password',
            'name' => $name,
            'label' => $label,
        ], $options);
        return $this;
    }

    public function number(string $name, string $label, array $options = []): static
    {
        $this->fields[] = array_merge([
            'type' => 'number',
            'name' => $name,
            'label' => $label,
        ], $options);
        return $this;
    }

    public function textarea(string $name, string $label, array $options = []): static
    {
        $this->fields[] = array_merge([
            'type' => 'textarea',
            'name' => $name,
            'label' => $label,
        ], $options);
        return $this;
    }

    public function richEditor(string $name, string $label, array $options = []): static
    {
        $this->fields[] = array_merge([
            'type' => 'richEditor',
            'name' => $name,
            'label' => $label,
        ], $options);
        return $this;
    }

    public function select(string $name, string $label, array $options = [], array $selectOptions = []): static
    {
        $this->fields[] = array_merge([
            'type' => 'select',
            'name' => $name,
            'label' => $label,
            'options' => $selectOptions,
        ], $options);
        return $this;
    }

    public function checkbox(string $name, string $label, array $options = []): static
    {
        $this->fields[] = array_merge([
            'type' => 'checkbox',
            'name' => $name,
            'label' => $label,
        ], $options);
        return $this;
    }

    public function radio(string $name, string $label, array $choices, array $options = []): static
    {
        $this->fields[] = array_merge([
            'type' => 'radio',
            'name' => $name,
            'label' => $label,
            'choices' => $choices,
        ], $options);
        return $this;
    }

    public function file(string $name, string $label, array $options = []): static
    {
        $this->multipart = true;
        $this->fields[] = array_merge([
            'type' => 'file',
            'name' => $name,
            'label' => $label,
        ], $options);
        return $this;
    }

    public function hidden(string $name, string $value): static
    {
        $this->fields[] = [
            'type' => 'hidden',
            'name' => $name,
            'value' => $value,
        ];
        return $this;
    }

    public function liveBind(string $field, string $property): static
    {
        $this->liveBind[$field] = $property;
        return $this;
    }

    public function fill(array $data): static
    {
        foreach ($this->fields as &$field) {
            $name = $field['name'] ?? '';
            if ($name && isset($data[$name])) {
                $field['value'] = $data[$name];
            }
        }
        return $this;
    }

    protected function toElement(): Element
    {
        $formEl = new Element('form');
        $this->buildElement($formEl);

        $formEl->attr('method', $this->method);
        if ($this->action) {
            $formEl->attr('action', $this->action);
        }
        if ($this->multipart) {
            $formEl->attr('enctype', 'multipart/form-data');
        }

        foreach ($this->fields as $field) {
            $formEl->child($this->renderField($field));
        }

        if ($this->submitLabel) {
            $btnGroupEl = Element::make('div')->class('ux-form-group');
            $btnGroupEl->child(
                Button::make()
                    ->label($this->submitLabel)
                    ->submit()
                    ->primary()
            );
            $formEl->child($btnGroupEl);
        }

        return $formEl;
    }

    private function renderField(array $field): Element
    {
        $type = $field['type'];

        if ($type === 'hidden') {
            return Element::make('input')
                ->attr('type', 'hidden')
                ->attr('name', $field['name'])
                ->attr('value', $field['value']);
        }

        $groupEl = Element::make('div')->class('ux-form-group');

        $name = $field['name'] ?? '';
        $label = $field['label'] ?? '';

        if ($label && $type !== 'checkbox') {
            $labelEl = Element::make('label')
                ->class('ux-form-label')
                ->attr('for', $name)
                ->text($label);

            if ($field['required'] ?? false) {
                $labelEl->child(Element::make('span')->class('ux-form-required')->text('*'));
            }

            $groupEl->child($labelEl);
        }

        $inputAttrs = $this->buildFieldAttrs($field);

        switch ($type) {
            case 'textarea':
                $textareaEl = Element::make('textarea');
                foreach ($inputAttrs as $key => $value) {
                    $textareaEl->attr($key, $value);
                }
                $textareaEl->text($field['value'] ?? '');
                $groupEl->child($textareaEl);
                break;

            case 'richEditor':
                $richEditor = new RichEditor();
                $richEditor->name($name)->label($label);

                if (isset($field['value'])) {
                    $richEditor->value($field['value']);
                }
                if (isset($field['placeholder'])) {
                    $richEditor->placeholder($field['placeholder']);
                }
                if (isset($field['required']) && $field['required']) {
                    $richEditor->required(true);
                }
                if (isset($field['disabled']) && $field['disabled']) {
                    $richEditor->disabled(true);
                }
                if (isset($field['toolbar'])) {
                    $richEditor->toolbar($field['toolbar']);
                }
                if (isset($field['minimal'])) {
                    $richEditor->minimal($field['minimal']);
                }
                if (isset($field['rows'])) {
                    $richEditor->rows($field['rows']);
                }
                if (isset($field['outputFormat'])) {
                    $richEditor->outputFormat($field['outputFormat']);
                }
                if (isset($field['help'])) {
                    $richEditor->help($field['help']);
                }

                $groupEl->child($richEditor->render());
                break;

            case 'select':
                $selectEl = Element::make('select');
                foreach ($inputAttrs as $key => $value) {
                    $selectEl->attr($key, $value);
                }
                foreach ($field['options'] as $value => $labelText) {
                    $optionEl = Element::make('option')
                        ->attr('value', (string)$value)
                        ->text($labelText);
                    if (($field['value'] ?? '') === (string)$value) {
                        $optionEl->attr('selected', '');
                    }
                    $selectEl->child($optionEl);
                }
                $groupEl->child($selectEl);
                break;

            case 'checkbox':
                $checked = ($field['checked'] ?? false) ? 'checked' : '';
                $checkboxLabelEl = Element::make('label')->class('ux-form-checkbox');
                $checkboxInputEl = Element::make('input')
                    ->attr('type', 'checkbox')
                    ->attr('name', $name)
                    ->attr('value', '1');
                foreach ($inputAttrs as $key => $value) {
                    if ($key !== 'name') {
                        $checkboxInputEl->attr($key, $value);
                    }
                }
                if ($checked) {
                    $checkboxInputEl->attr('checked', '');
                }
                $checkboxLabelEl->child($checkboxInputEl);
                $checkboxLabelEl->child(Element::make('span')->text($label));
                $groupEl->child($checkboxLabelEl);
                break;

            case 'radio':
                foreach ($field['choices'] as $value => $labelText) {
                    $radioLabelEl = Element::make('label')->class('ux-form-radio');
                    $radioInputEl = Element::make('input')
                        ->attr('type', 'radio')
                        ->attr('name', $name)
                        ->attr('value', (string)$value);
                    foreach ($inputAttrs as $key => $attrValue) {
                        if ($key !== 'name') {
                            $radioInputEl->attr($key, $attrValue);
                        }
                    }
                    if (($field['value'] ?? '') === (string)$value) {
                        $radioInputEl->attr('checked', '');
                    }
                    $radioLabelEl->child($radioInputEl);
                    $radioLabelEl->child(Element::make('span')->text($labelText));
                    $groupEl->child($radioLabelEl);
                }
                break;

            case 'file':
                $fileInputEl = Element::make('input')
                    ->attr('type', 'file');
                foreach ($inputAttrs as $key => $value) {
                    $fileInputEl->attr($key, $value);
                }
                $groupEl->child($fileInputEl);
                break;

            default:
                $inputEl = Element::make('input')
                    ->attr('type', $type);
                foreach ($inputAttrs as $key => $value) {
                    $inputEl->attr($key, $value);
                }
                if (isset($field['value'])) {
                    $inputEl->attr('value', (string)$field['value']);
                }
                $groupEl->child($inputEl);
        }

        if (isset($field['help'])) {
            $groupEl->child(Element::make('span')->class('ux-form-help')->text($field['help']));
        }

        return $groupEl;
    }

    private function buildFieldAttrs(array $field): array
    {
        $attrs = [];
        $attrs['id'] = $field['name'];
        $attrs['name'] = $field['name'];

        if (isset($field['placeholder'])) {
            $attrs['placeholder'] = $field['placeholder'];
        }

        if ($field['required'] ?? false) {
            $attrs['required'] = '';
        }

        if (isset($field['disabled']) && $field['disabled']) {
            $attrs['disabled'] = '';
        }

        if (isset($field['readonly']) && $field['readonly']) {
            $attrs['readonly'] = '';
        }

        if (isset($this->liveBind[$field['name']])) {
            $attrs['data-bind'] = $this->liveBind[$field['name']];
        }

        if (isset($field['class'])) {
            $attrs['class'] = $field['class'];
        } else {
            $attrs['class'] = 'ux-form-input';
        }

        if (isset($field['autocomplete'])) {
            $attrs['autocomplete'] = $field['autocomplete'];
        } else {
            $type = $field['type'] ?? 'text';
            $autocompleteMap = [
                'password' => 'current-password',
                'email' => 'email',
                'username' => 'username',
                'tel' => 'tel',
            ];
            if (isset($autocompleteMap[$type])) {
                $attrs['autocomplete'] = $autocompleteMap[$type];
            }
        }

        return $attrs;
    }
}
