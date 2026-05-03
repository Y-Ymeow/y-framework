<?php

declare(strict_types=1);

namespace Framework\View;

use Framework\View\Base\Element;

/**
 * Form — 表单及表单元素
 *
 *   Form::make()->action('/save')->method('POST')
 *       ->child(Form::input('email')->label('邮箱')->required())
 *       ->child(Form::password('pass')->label('密码'))
 *       ->child(Form::select('role')->options(['admin' => '管理员', 'user' => '用户']))
 *       ->child(Form::textarea('bio')->label('简介'))
 *       ->child(Form::button('提交')->primary())
 */
class Form extends Element
{
    public function __construct()
    {
        parent::__construct('form');
    }

    public function action(string $url): static
    {
        $this->attrs['action'] = $url;
        return $this;
    }

    public function method(string $method): static
    {
        $this->attrs['method'] = strtoupper($method);
        return $this;
    }

    public function multipart(): static
    {
        $this->attrs['enctype'] = 'multipart/form-data';
        return $this;
    }

    // ========== 表单元素工厂 ==========

    public static function input(string $name = ''): FormInput
    {
        return FormInput::make($name);
    }

    public static function password(string $name = ''): FormInput
    {
        return FormInput::make($name)->type('password');
    }

    public static function email(string $name = ''): FormInput
    {
        return FormInput::make($name)->type('email');
    }

    public static function number(string $name = ''): FormInput
    {
        return FormInput::make($name)->type('number');
    }

    public static function hiddenField(string $name = ''): FormInput
    {
        return FormInput::make($name)->type('hidden');
    }

    public static function textarea(string $name = ''): FormTextarea
    {
        return FormTextarea::make($name);
    }

    public static function select(string $name = ''): FormSelect
    {
        return FormSelect::make($name);
    }

    public static function checkbox(string $name = ''): FormInput
    {
        return FormInput::make($name)->type('checkbox');
    }

    public static function button(string $label = ''): FormButton
    {
        return FormButton::make($label);
    }

    public static function submit(string $label = '提交'): FormButton
    {
        return FormButton::make($label)->type('submit');
    }
    public static function label(string $text = ''): Element
    {
        return Element::make('label')->text($text);
    }
}

class FormInput extends Element
{
    private ?string $labelText = null;

    public function __construct(string $name = '')
    {
        parent::__construct('input');
        if ($name) $this->attrs['name'] = $name;
    }

    public function name(string $name): static { $this->attrs['name'] = $name; return $this; }
    public function type(string $type): static { $this->attrs['type'] = $type; return $this; }
    public function value(string $value): static { $this->attrs['value'] = $value; return $this; }
    public function placeholder(string $text): static { $this->attrs['placeholder'] = $value ?? $text; return $this; }
    public function required(): static { $this->attrs['required'] = ''; return $this; }
    public function disabled(): static { $this->attrs['disabled'] = ''; return $this; }
    public function readonly(): static { $this->attrs['readonly'] = ''; return $this; }
    public function label(string $text): static { $this->labelText = $text; return $this; }

    public function model(string $expr): static
    {
        $this->attrs['y-model'] = $expr;
        return $this;
    }

    public function render(): string
    {
        if ($this->labelText) {
            $wrapper = new Element('div');
            $wrapper->class('space-y-1');
            $lbl = new Element('label');
            $lbl->class('block text-sm font-medium text-gray-700');
            $lbl->text($this->labelText);
            if (isset($this->attrs['name'])) $lbl->attr('for', $this->attrs['name']);
            $wrapper->child($lbl);
            $this->class('w-full border rounded-md px-3 py-2 text-sm');
            $wrapper->child($this);
            return $wrapper->render();
        }
        $this->class('border rounded-md px-3 py-2 text-sm');
        return parent::render();
    }
}

class FormTextarea extends Element
{
    private ?string $labelText = null;

    public function __construct(string $name = '')
    {
        parent::__construct('textarea');
        if ($name) $this->attrs['name'] = $name;
    }

    public function name(string $name): static { $this->attrs['name'] = $name; return $this; }
    public function placeholder(string $text): static { $this->attrs['placeholder'] = $text; return $this; }
    public function rows(int $n): static { $this->attrs['rows'] = (string)$n; return $this; }
    public function required(): static { $this->attrs['required'] = ''; return $this; }
    public function label(string $text): static { $this->labelText = $text; return $this; }
    public function model(string $expr): static { $this->attrs['y-model'] = $expr; return $this; }

    public function render(): string
    {
        $this->class('w-full border rounded-md px-3 py-2 text-sm');
        return parent::render();
    }
}

class FormSelect extends Element
{
    private array $options = [];
    private ?string $labelText = null;

    public function __construct(string $name = '')
    {
        parent::__construct('select');
        if ($name) $this->attrs['name'] = $name;
    }

    public function name(string $name): static { $this->attrs['name'] = $name; return $this; }
    public function required(): static { $this->attrs['required'] = ''; return $this; }
    public function label(string $text): static { $this->labelText = $text; return $this; }
    public function model(string $expr): static { $this->attrs['y-model'] = $expr; return $this; }

    public function options(array $options): static
    {
        $this->options = $options;
        return $this;
    }

    public function render(): string
    {
        foreach ($this->options as $value => $label) {
            $opt = new Element('option');
            $opt->attr('value', (string)$value);
            $opt->text((string)$label);
            $this->child($opt);
        }
        $this->class('w-full border rounded-md px-3 py-2 text-sm');
        return parent::render();
    }
}

class FormButton extends Element
{
    public function __construct(string $label = '')
    {
        parent::__construct('button');
        if ($label) $this->text($label);
        $this->attrs['type'] = 'button';
    }

    public function type(string $type): static { $this->attrs['type'] = $type; return $this; }
    public function primary(): static { return $this->class('px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700'); }
    public function danger(): static { return $this->class('px-4 py-2 bg-red-600 text-white rounded-md text-sm hover:bg-red-700'); }
    public function outline(): static { return $this->class('px-4 py-2 border border-gray-300 text-gray-700 rounded-md text-sm hover:bg-gray-50'); }
    public function sm(): static { return $this->class('px-3 py-1.5 text-xs'); }
}
