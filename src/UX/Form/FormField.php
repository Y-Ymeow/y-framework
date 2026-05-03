<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 表单字段基类
 *
 * 所有表单输入组件的抽象基类，提供标签、验证、禁用、只读、Live 绑定等通用表单能力。
 *
 * @ux-category Form
 * @ux-since 1.0.0
 * @ux-example Input::make()->name('username')->label('用户名')->required()
 * @ux-example Input::make()->name('email')->label('邮箱')->liveModel('email')->error('邮箱格式错误')
 * @ux-js-component —
 * @ux-css form.css
 */
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

    /**
     * 设置字段名称
     * @param string $name 字段名
     * @return static
     * @ux-example Input::make()->name('username')
     */
    public function name(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    /**
     * 设置标签文字
     * @param string $label 标签文字
     * @return static
     * @ux-example Input::make()->label('用户名')
     */
    public function label(string $label): static
    {
        $this->label = $label;
        return $this;
    }

    /**
     * 设置必填状态
     * @param bool $required 是否必填
     * @return static
     * @ux-example Input::make()->required()
     * @ux-default true
     */
    public function required(bool $required = true): static
    {
        $this->required = $required;
        return $this;
    }

    /**
     * 设置默认值
     * @param mixed $value 默认值
     * @return static
     * @ux-example Input::make()->value('默认值')
     */
    public function value(mixed $value): static
    {
        $this->value = $value;
        return $this;
    }

    /**
     * 设置占位文本
     * @param string $placeholder 占位提示
     * @return static
     * @ux-example Input::make()->placeholder('请输入...')
     */
    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    /**
     * 设置帮助文本
     * @param string $help 帮助文本（支持 HTML）
     * @return static
     * @ux-example Input::make()->help('请输入有效的邮箱地址')
     */
    public function help(string $help): static
    {
        $this->help = $help;
        return $this;
    }

    /**
     * 设置禁用状态
     * @param bool $disabled 是否禁用
     * @return static
     * @ux-example Input::make()->disabled()
     * @ux-default true
     */
    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;
        return $this;
    }

    /**
     * 设置只读状态
     * @param bool $readonly 是否只读
     * @return static
     * @ux-example Input::make()->readonly()
     * @ux-default true
     */
    public function readonly(bool $readonly = true): static
    {
        $this->readonly = $readonly;
        return $this;
    }

    /**
     * 设置自动完成属性
     * @param string $autocomplete 自动完成值（如 email, username）
     * @return static
     * @ux-example Input::make()->autocomplete('email')
     */
    public function autocomplete(string $autocomplete): static
    {
        $this->autocomplete = $autocomplete;
        return $this;
    }

    /**
     * 设置验证规则
     * @param array $rules 规则数组
     * @return static
     * @ux-example Input::make()->rules(['required', 'email', 'min:5'])
     */
    public function rules(array $rules): static
    {
        $this->rules = $rules;
        return $this;
    }

    /**
     * 绑定 Live 属性
     * @param string $property LiveComponent 属性名
     * @return static
     * @ux-example Input::make()->liveModel('email')
     */
    public function liveModel(string $property): static
    {
        $this->liveModel = $property;
        return $this;
    }

    /**
     * 设置验证错误信息
     * @param string|null $message 错误信息，传 null 则清除错误
     * @return static
     * @ux-example Input::make()->error('邮箱格式错误')
     */
    public function error(?string $message = null): static
    {
        $this->error = $message;
        $this->invalid = $message !== null;
        return $this;
    }

    /**
     * 设置无效状态（不显示错误信息）
     * @param bool $invalid 是否无效
     * @return static
     */
    public function invalid(bool $invalid = true): static
    {
        $this->invalid = $invalid;
        return $this;
    }

    /**
     * 获取错误信息
     * @return string|null
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * 是否处于无效状态
     * @return bool
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
