<?php

declare(strict_types=1);

namespace Framework\View\Concerns;

/**
 * 响应式指令 trait
 *
 * 提供前端 y-directive 引擎的声明式绑定方法。
 * 这些属性由前端 y-directive 引擎解析，实现类似 Vue 的响应式绑定。
 *
 * @view-category 响应式指令
 * @view-since 1.0.0
 */
trait HasBindDirectives
{
    /**
     * 文本绑定指令（data-text）
     *
     * 响应式显示文本内容
     *
     * @view-since 1.0.0
     * @param string $expr 表达式（如 'user.name'）
     * @return static
     */
    public function bindText(string $expr): static
    {
        $this->attrs['data-text'] = $expr;
        return $this;
    }

    /**
     * HTML 绑定指令（data-html）
     *
     * 响应式显示 HTML 内容（经过安全过滤）
     *
     * @view-since 1.0.0
     * @param string $expr 表达式
     * @return static
     */
    public function bindHtml(string $expr): static
    {
        $this->attrs['data-html'] = $expr;
        return $this;
    }

    /**
     * 模型绑定指令（data-model）— 别名方法
     *
     * @view-since 1.0.0
     * @param string $key 属性键名
     * @return static
     */
    public function bindModel(string $key): static
    {
        $this->attrs['data-model'] = $key;
        return $this;
    }

    /**
     * 显示/隐藏绑定（data-show）
     *
     * 根据表达式控制元素可见性（display: none）
     *
     * @view-since 1.0.0
     * @param string $expr 条件表达式
     * @return static
     */
    public function bindShow(string $expr): static
    {
        $this->attrs['data-show'] = $expr;
        return $this;
    }

    /**
     * 条件渲染绑定（data-if）
     *
     * 根据条件决定是否渲染元素到 DOM
     *
     * @view-since 1.0.0
     * @param string $expr 条件表达式
     * @return static
     */
    public function bindIf(string $expr): static
    {
        $this->attrs['data-if'] = $expr;
        return $this;
    }

    /**
     * 循环渲染绑定（data-for）
     *
     * 遍历数组生成多个子元素
     *
     * @view-since 1.0.0
     * @param string $expr 循环表达式（如 'item in items'）
     * @return static
     */
    public function bindFor(string $expr): static
    {
        $this->attrs['data-for'] = $expr;
        return $this;
    }

    /**
     * 事件绑定指令（data-on:event）
     *
     * 绑定 DOM 事件到 LiveComponent 方法
     *
     * @view-since 1.0.0
     * @param string $event 事件名（click, input, change 等）
     * @param string $expr 要调用的方法名或表达式
     * @return static
     *
     * @view-example
     * Element::make('button')->bindOn('click', 'submitForm');
     * // 点击按钮时调用 LiveComponent 的 submitForm() 方法
     * @view-example-end
     */
    public function bindOn(string $event, string $expr): static
    {
        $this->attrs["data-on:{$event}"] = $expr;
        return $this;
    }

    /**
     * 属性绑定指令（data-bind:attr）
     *
     * 动态设置 HTML 属性值
     *
     * @view-since 1.0.0
     * @param string $attr 要绑定的属性名
     * @param string|array $expr 表达式或值
     * @return static
     */
    public function bindAttr(string $attr, string|array $expr): static
    {
        $this->attrs["data-bind:{$attr}"] = is_array($expr) ? json_encode($expr) : $expr;
        return $this;
    }

    /**
     * CSS 类绑定（data-bind:class）
     *
     * 根据条件动态切换 CSS 类
     *
     * @view-since 1.0.0
     * @param string $expr 类绑定表达式
     * @return static
     */
    public function dataClass(string $expr): static
    {
        $this->attrs['data-bind:class'] = $expr;
        return $this;
    }

    /**
     * 副作用绑定（data-effect）
     *
     * 当依赖数据变化时执行副作用
     *
     * @view-since 1.0.0
     * @param string $expr 效果表达式
     * @return static
     */
    public function bindEffect(string $expr): static
    {
        $this->attrs['data-effect'] = $expr;
        return $this;
    }

    /**
     * 引用绑定（data-ref）
     *
     * 注册 DOM 引用，可在 JS 中通过 ref 访问
     *
     * @view-since 1.0.0
     * @param string $name 引用名称
     * @return static
     */
    public function bindRef(string $name): static
    {
        $this->attrs['data-ref'] = $name;
        return $this;
    }

    /**
     * 过渡动画绑定（data-transition）
     *
     * 元素显示/隐藏时应用过渡效果
     *
     * @view-since 1.0.0
     * @param string $expr 过渡名称或配置
     * @return static
     */
    public function bindTransition(string $expr): static
    {
        $this->attrs['data-transition'] = $expr;
        return $this;
    }
}
