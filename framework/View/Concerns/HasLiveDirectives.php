<?php

declare(strict_types=1);

namespace Framework\View\Concerns;

/**
 * LiveComponent 指令 trait
 *
 * 提供 LiveComponent 集成相关的 data-* 属性绑定方法。
 * 这些方法在元素上设置 LiveComponent 专用的交互属性。
 *
 * @view-category LiveComponent 集成
 * @view-since 1.0.0
 */
trait HasLiveDirectives
{
    /**
     * LiveComponent 双向绑定（增强版）
     *
     * 同时设置 data-live-model 和 data-model，启用实时同步
     *
     * @view-since 1.0.0
     * @param string $name LiveComponent 属性名
     * @return static
     *
     * @view-example
     * // 在 LiveComponent 中使用
     * public string $username = '';
     *
     * public function render(): Element
     * {
     *     return Element::make('input')
     *         ->liveModel('username')
     *         ->attr('type', 'text');
     * }
     * // 输入框的值会自动同步到 $this->username
     * @view-example-end
     */
    public function liveModel(string $name): static
    {
        $this->attrs['data-live-model'] = $name;
        $this->model($name);
        return $this;
    }

    /**
     * LiveComponent Action 绑定（data-live-action / data-action）
     *
     * 触发事件时调用 LiveComponent 的指定方法。
     * 支持三种格式：
     * 1. ->liveAction('save')                    → data-live-action:click="save"
     * 2. ->liveAction('save', 'input')           → data-live-action:input="save"
     * 3. ->liveAction('search', 'input', true)   → 兼容旧格式 data-action + data-action-event
     *
     * @view-since 1.0.0
     * @param string $action 方法名
     * @param string $event 事件类型（默认 click）
     * @param bool $legacyAttrs 使用旧的 data-action / data-action-event 属性名
     * @view-default false
     * @return static
     */
    public function liveAction(string $action, string $event = 'click', mixed $params = []): static
    {
        $actionExpr = $action;
        if (is_array($params) && !empty($params)) {
            // Check if it's an associative array
            ksort($params);

            $paramString = [];
            foreach ($params as $key => $value) {
                if (is_numeric($key)) {
                    $paramString[] = $value;
                } else {
                    if (is_bool($value)) {
                        $value = $value ? 'true' : 'false';
                    } elseif (is_null($value)) {
                        $value = 'null';
                    } elseif (is_array($value)) {
                        $value = json_encode($value);
                    } elseif (is_object($value)) {
                        $value = json_encode($value);
                    } elseif (is_string($value)) {
                        $value = "'{$value}'";
                    }
                    $paramString[] = "{$key}: {$value}";
                }
            }

            $actionExpr .= '(' . implode(', ', $paramString) . ')';
        } elseif (!str_contains($action, '(')) {
            $actionExpr .= '()';
        }

        $this->attrs['data-action:' . $event] = $actionExpr;
        return $this;
    }

    /**
     * LiveComponent 数据绑定（data-bind / data-bind-type）
     *
     * 将元素绑定到 LiveComponent 属性，支持多种绑定类型
     *
     * @view-since 1.0.0
     * @param string $key 属性键名
     * @param string $type 绑定类型：text（默认）, value, checked 等
     * @return static
     */
    public function liveBind(string $key, string $type = 'text'): static
    {
        if ($type === 'text') {
            $this->attrs['data-bind'] = $key;
        } else {
            $this->attrs["data-bind-{$type}"] = $key;
        }
        return $this;
    }

    /**
     * 声明 LiveComponent 分片更新区域（data-live-fragment）
     *
     * 标记该区域可独立更新，无需刷新整个组件
     *
     * @view-since 1.0.0
     * @param string $name 分片名称（唯一标识）
     * @return static
     */
    public function liveFragment(string $name): static
    {
        $this->attrs['data-live-fragment'] = $name;
        \Framework\View\FragmentRegistry::getInstance()->record($this->attrs['data-live-fragment'], $this);
        return $this;
    }

    /**
     * LiveComponent Action 禁用条件（data-live-disabled）
     *
     * 当表达式求值为真时，阻止 Action 触发。
     *
     * @view-since 1.0.0
     * @param string $expr 表达式（如 'count === 0'）
     * @return static
     */
    public function liveDisabled(string $expr): static
    {
        $this->attrs['data-live-disabled'] = $expr;
        return $this;
    }

    /**
     * 订阅 SSE 频道（data-live-sse）
     *
     * 元素将自动订阅指定的 SSE 频道，接收服务器推送的消息。
     * 当收到 `live:action` 事件时，会自动调用对应的 LiveAction。
     *
     * @view-since 2.0
     * @param string ...$channels 频道名称列表
     * @return static
     *
     * @view-example
     * // 订阅单个频道
     * Element::make('div')
     *     ->id('dashboard')
     *     ->dataLiveSse('dashboard')
     *
     * // 订阅多个频道
     * Element::make('div')
     *     ->dataLiveSse('notifications', 'orders', 'system')
     * @view-example-end
     */
    public function dataLiveSse(string ...$channels): static
    {
        $this->attrs['data-live-sse'] = implode(',', $channels);
        return $this;
    }

    abstract public function model(string $name): static;
}
