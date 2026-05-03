<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\View\Base\Element;

/**
 * 输入框
 *
 * 基础文本输入组件，支持多种 HTML 类型（text、email、password、number、date 等）。
 *
 * @ux-category Form
 * @ux-since 1.0.0
 * @ux-example Input::make()->label('用户名')->model('username')
 * @ux-example Input::make()->label('邮箱')->email()->model('email')
 * @ux-example Input::make()->label('密码')->password()
 * @ux-js-component —
 * @ux-css form.css
 */
class Input extends FormField
{
    protected string $type = 'text';

    /**
     * 设置输入类型
     * @param string $type 类型：text/email/password/number 等
     * @return static
     * @ux-example Input::make()->type('email')
     * @ux-default 'text'
     */
    public function type(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    /**
     * 设置为邮箱类型
     * @return static
     * @ux-example Input::make()->email()
     */
    public function email(): static
    {
        return $this->type('email');
    }

    /**
     * 设置为密码类型
     * @return static
     * @ux-example Input::make()->password()
     */
    public function password(): static
    {
        return $this->type('password');
    }

    /**
     * 设置为数字类型
     * @return static
     * @ux-example Input::make()->number()
     */
    public function number(): static
    {
        return $this->type('number');
    }

    /**
     * 设置为日期类型
     * @return static
     * @ux-example Input::make()->date()
     */
    public function date(): static
    {
        return $this->type('date');
    }

    /**
     * 设置为日期时间类型
     * @return static
     * @ux-example Input::make()->datetime()
     */
    public function datetime(): static
    {
        return $this->type('datetime-local');
    }

    /**
     * 设置为时间类型
     * @return static
     * @ux-example Input::make()->time()
     */
    public function time(): static
    {
        return $this->type('time');
    }

    /**
     * 设置为 URL 类型
     * @return static
     * @ux-example Input::make()->url()
     */
    public function url(): static
    {
        return $this->type('url');
    }

    /**
     * 设置为电话类型
     * @return static
     * @ux-example Input::make()->tel()
     */
    public function tel(): static
    {
        return $this->type('tel');
    }

    /**
     * 设置为搜索类型
     * @return static
     * @ux-example Input::make()->search()
     */
    public function search(): static
    {
        return $this->type('search');
    }

    /**
     * 设置为颜色类型
     * @return static
     * @ux-example Input::make()->color()
     */
    public function color(): static
    {
        return $this->type('color');
    }

    /**
     * 设置为范围类型
     * @return static
     * @ux-example Input::make()->range()
     */
    public function range(): static
    {
        return $this->type('range');
    }

    protected function toElement(): Element
    {
        $groupEl = Element::make('div')->class('ux-form-group');

        $labelEl = $this->renderLabel();
        if ($labelEl) {
            $groupEl->child($labelEl);
        }

        $inputEl = Element::make('input')
            ->attr('type', $this->type)
            ->class('ux-form-input');

        foreach ($this->buildFieldAttrs() as $key => $value) {
            $inputEl->attr($key, $value);
        }

        if ($this->value !== null) {
            $inputEl->attr('value', (string)$this->value);
        }

        $groupEl->child($inputEl);

        $helpEl = $this->renderHelp();
        if ($helpEl) {
            $groupEl->child($helpEl);
        }

        return $groupEl;
    }
}
