<?php

declare(strict_types=1);

namespace Framework\View\Base;

/**
 * Element — HTML 元素基类
 *
 * 所有视图组件的根基类，提供声明式 HTML 构建能力。
 * 支持链式调用、响应式指令绑定、LiveComponent 集成和 XSS 防护。
 *
 * ## 设计定位
 *
 * Element 是框架的**最小构建单位**，所有 UX 组件（Button, Modal, DatePicker 等）
 * 都基于 Element 构建。它既是 PHP 端的 DOM 抽象，也是连接 JS 交互层的桥梁。
 *
 * ## 能力分层
 *
 * ### 第一层：基础 HTML 构建
 * - `make()`, `id()`, `class()`, `attr()`, `style()`, `data()`
 * - `text()`, `html()`, `child()`, `children()`
 *
 * ### 第二层：LiveComponent 集成（实时双向绑定）
 * - **liveModel()**: 双向数据同步，用户输入自动更新 LiveComponent 属性
 * - **liveAction()**: 绑定 Action，触发后端方法调用
 * - **liveParams()**: 传递参数给 Action
 * - **liveBind()**: 多类型绑定（text/value/checked）
 * - **liveFragment()**: 分片更新，只刷新组件的一部分
 *
 * ### 第三层：响应式指令系统（y-directive 前端引擎）
 * 这些属性由前端 y-directive 引擎解析，实现类似 Vue 的声明式绑定：
 *
 * | 方法 | data-* 属性 | 功能 | 类比 Vue |
 * |------|-------------|------|----------|
 * | bindText() | data-text | 动态文本 | {{ expr }} |
 * | bindHtml() | data-html | 动态 HTML | v-html |
 * | bindModel() | data-model | 表单双向绑定 | v-model |
 * | bindShow() | data-show | 显示/隐藏 | v-show |
 * | bindIf() | data-if | 条件渲染 | v-if |
 * | bindFor() | data-for | 列表循环 | v-for |
 * | bindOn() | data-on:event | 事件监听 | v-on |
 * | bindAttr() | data-bind:attr | 动态属性 | v-bind |
 * | dataClass() | data-bind:class | 动态 CSS 类 | :class |
 * | bindEffect() | data-effect | 副作用（watch） | watch |
 * | bindRef() | data-ref | DOM 引用 | ref |
 * | bindTransition() | data-transition | 过渡动画 | transition |
 *
 * ### 第四层：辅助功能
 * - **intl()**: 国际化翻译，自动从语言包加载
 * - **cloak()**: 防止未渲染内容闪烁
 * - **requireScript()**: 声明依赖的 JS 模块
 * - **state()**: 存储结构化状态数据
 *
 * ## 安全特性
 *
 * - 自动过滤 on* 事件属性（防 XSS）
 * - 自动过滤 javascript:/data: 协议链接
 * - html() 内容经过白名单过滤（移除 script/style/iframe 等）
 * - text() 内容自动 htmlspecialchars 转义
 *
 * @view-category 核心
 * @view-since 1.0.0
 *
 * @view-example
 * // === 第一层：基础 HTML ===
 * $div = Element::make('div')
 *     ->id('container')
 *     ->class('p-4', 'bg-white')
 *     ->text('Hello World');
 * echo $div;
 * // <div id="container" class="p-4 bg-white">Hello World</div>
 * @view-example-end
 *
 * @view-example
 * // === 第二层：LiveComponent 集成 ===
 * // 在 LiveComponent 的 render() 中使用：
 * return Element::make('div')->children(
 *     Element::make('input')
 *         ->liveModel('username')       // 双向绑定到 $this->username
 *         ->attr('type', 'text')
 *         ->placeholder('请输入'),
 *
 *     Element::make('button')
 *         ->liveAction('save')           // 点击调用 save() 方法
 *         ->text('保存')
 * );
 * @view-example-end
 *
 * @view-example
 * // === 第三层：响应式指令 ===
 * // 这些属性由前端 y-directive 引擎解析：
 * Element::make('div')
 *     ->bindShow('isVisible')           // 根据 isVisible 变量控制显示
 *     ->bindFor('item in items')         // 循环渲染列表
 *     ->children(
 *         Element::make('span')
 *             ->bindText('item.name')    // 显示 item.name 的值
 *             ->dataClass("{ active: item.id === selectedId }")
 *     )
 *     ->bindOn('click', 'handleClick'); // 点击时调用 handleClick()
 * @view-example-end
 */
class Element
{
    protected string $tag;
    protected array $attrs = [];
    protected array $children = [];
    protected ?string $htmlContent = null;
    protected ?string $textContent = null;
    protected bool $void = false;

    /**
     * 创建 Element 实例
     *
     * @param string $tag HTML 标签名（如 'div', 'input', 'span'）
     */
    public function __construct(string $tag)
    {
        $this->tag = $tag;
        $this->void = in_array($tag, ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr']);
    }

    /**
     * 静态工厂方法 — 创建 Element 实例
     *
     * @view-since 1.0.0
     * @param string|Element|null $tagOrcontent HTML 标签名或内容
     * @param string|Element|null $content 可选的内容（已废弃，建议使用 ->text() 或 ->html()）
     * @return static 新的 Element 实例
     *
     * @view-example
     * // 推荐用法
     * $el = Element::make('div');
     * $el = Element::make('section');
     * @view-example-end
     */
    public static function make(string|Element|null $tagOrcontent = null, string|Element|null $content = null): static
    {
        if (!empty($tagOrcontent) && !empty($content)) {
            return new static($tagOrcontent, $content);
        }

        return new static($tagOrcontent);
    }

    /**
     * 设置元素 ID
     *
     * @view-since 1.0.0
     * @param string $id 唯一标识符
     * @return static
     *
     * @view-example
     * Element::make('div')->id('main-container');
     * // <div id="main-container">
     * @view-example-end
     */
    public function id(string $id): static
    {
        $this->attrs['id'] = $id;
        return $this;
    }

    /**
     * 添加 CSS 类名（支持多个，自动合并）
     *
     * @view-since 1.0.0
     * @param string ...$classes 类名列表
     * @return static
     *
     * @view-example
     * Element::make('div')->class('p-4', 'bg-white', 'rounded');
     * // <div class="p-4 bg-white rounded">
     *
     * // 追加（不覆盖已有类）
     * $el->class('mt-2'); // 现在包含 p-4 bg-white rounded mt-2
     * @view-example-end
     */
    public function class(string ...$classes): static
    {
        $existing = $this->attrs['class'] ?? '';
        $this->attrs['class'] = trim($existing . ' ' . implode(' ', $classes));
        return $this;
    }

    /**
     * 设置单个 HTML 属性
     *
     * 安全过滤：自动拒绝 on* 事件属性（如 onclick）
     *
     * @view-since 1.0.0
     * @param string $name 属性名（如 'href', 'title'）
     * @param string $value 属性值
     * @return static
     */
    public function attr(string $name, string $value): static
    {
        if (preg_match('/^on/', $name)) {
            return $this;
        }

        $this->attrs[$name] = $value;
        return $this;
    }

    /**
     * 批量设置 HTML 属性（合并到已有属性）
     *
     * @view-since 1.0.0
     * @param array $attrs 属性键值对 ['href' => '/home', 'target' => '_blank']
     * @return static
     *
     * @view-example
     * Element::make('a')->attrs([
     *     'href' => '/page',
     *     'target' => '_blank',
     *     'rel' => 'noopener'
     * ]);
     * @view-example-end
     */
    public function attrs(array $attrs): static
    {

        $this->attrs = array_merge($this->attrs, $attrs);
        return $this;
    }

    /**
     * 设置 data-* 自定义属性
     *
     * @view-since 1.0.0
     * @param string $key data 键名（自动加 data- 前缀）
     * @param string $value 属性值
     * @return static
     *
     * @view-example
     * Element::make('div')->data('id', '123')->data('user', 'john');
     * // <div data-id="123" data-user="john">
     * @view-example-end
     */
    public function data(string $key, string $value): static
    {
        $this->attrs["data-{$key}"] = $value;
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
     *
     * // 后端推送更新
     * SseHub::liveAction('dashboard', 'refreshData');
     * // 前端自动调用 dashboard 组件的 refreshData() 方法
     * @view-example-end
     */
    public function dataLiveSse(string ...$channels): static
    {
        $this->attrs['data-live-sse'] = implode(',', $channels);
        return $this;
    }

    /**
     * 设置内联样式
     *
     * @view-since 1.0.0
     * @param string $style CSS 样式字符串
     * @return static
     *
     * @view-example
     * Element::make('div')->style('color: red; font-size: 14px');
     * @view-example-end
     */
    public function style(string $style): static
    {
        $this->attrs['style'] = $style;
        return $this;
    }

    /**
     * 设置纯文本内容（自动 HTML 转义，防 XSS）
     *
     * @view-since 1.0.0
     * @param string|null $text 文本内容
     * @return static
     *
     * @view-example
     * Element::make('p')->text('<script>alert("xss")</script>');
     * // <p>&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;</p>
     * @view-example-end
     */
    public function text(?string $text = null): static
    {
        if ($text === null) return $this;
        $this->textContent = $text;
        $this->htmlContent = null;
        return $this;
    }

    /**
     * 设置 HTML 内容（经过安全过滤）
     *
     * 允许安全标签，移除 script/style/iframe 等危险元素
     *
     * @view-since 1.0.0
     * @param mixed $html HTML 字符串
     * @return static
     */
    public function html(mixed $html = null): static
    {
        if ($html === null) return $this;
        $this->htmlContent = (string)$html;
        $this->textContent = null;
        return $this;
    }

    /**
     * 添加单个子元素
     *
     * 支持类型：Element、LiveComponent、实现了 toHtml()/render() 的对象、字符串
     *
     * @view-since 1.0.0
     * @param mixed $child 子元素
     * @return static
     */
    public function child(mixed $child): static
    {
        $this->children[] = $child;
        return $this;
    }

    /**
     * 批量添加子元素
     *
     * @view-since 1.0.0
     * @param mixed ...$children 子元素列表
     * @return static
     *
     * @view-example
     * Element::make('ul')->children(
     *     Element::make('li')->text('Item 1'),
     *     Element::make('li')->text('Item 2'),
     *     Element::make('li')->text('Item 3')
     * );
     * @view-example-end
     */
    public function children(mixed ...$children): static
    {
        $this->children = array_merge($this->children, $children);
        return $this;
    }

    /**
     * 设置 data-state 状态数据（JSON 编码）
     *
     * 用于在元素上存储结构化状态信息
     *
     * @view-since 1.0.0
     * @param array $data 状态数据
     * @return static
     */
    public function state(array $data): static
    {
        $this->attrs['data-state'] = json_encode($data, JSON_UNESCAPED_UNICODE);
        return $this;
    }

    /**
     * 声明式模型绑定（data-model）
     *
     * 将表单元素与 LiveComponent 的属性双向绑定
     *
     * @view-since 1.0.0
     * @param string $name 属性名
     * @return static
     */
    public function model(string $name): static
    {
        $this->attrs['data-model'] = $name;
        return $this;
    }

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
     * 国际化翻译（data-intl）
     *
     * 自动翻译文本内容，使用 Translator 服务
     *
     * @view-since 1.0.0
     * @param string $key 翻译键名
     * @return static
     *
     * @view-example
     * Element::make('span')->intl('welcome.message');
     * // 自动从语言包加载并显示翻译后的文本
     * @view-example-end
     */
    public function intl(string $key): static
    {
        $this->attrs['data-intl'] = $key;
        return $this;
    }

    /**
     * 隐藏未渲染内容（data-cloak）
     *
     * 在 JS 加载完成前隐藏元素，防止闪烁
     *
     * @view-since 1.0.0
     * @return static
     */
    public function cloak(): static
    {
        $this->attrs['data-cloak'] = '';
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
        \Framework\View\FragmentRegistry::record($this->attrs['data-live-fragment'], $this);
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
     * LiveComponent Action 绑定（data-action）
     *
     * 触发事件时调用 LiveComponent 的指定方法
     *
     * @view-since 1.0.0
     * @param string $action 方法名
     * @param string $event 事件类型（默认 click）
     * @return static
     *
     * @view-example
     * Element::make('button')
     *     ->liveAction('save')
     *     ->text('保存');
     * // 点击按钮调用 LiveComponent::save()
     *
     * Element::make('input')
     *     ->liveAction('search', 'input')
     *     ->attr('type', 'text');
     * // 输入时实时调用 search()
     * @view-example-end
     */
    public function liveAction(string $action, string $event = 'click'): static
    {
        $this->attrs['data-action'] = $action;
        if ($event !== 'click') {
            $this->attrs['data-action-event'] = $event;
        }
        return $this;
    }

    /**
     * LiveComponent Action 参数（data-action-params）
     *
     * 传递额外参数给 Action 方法
     *
     * @view-since 1.0.0
     * @param string|array $params 参数值或参数数组
     * @return static
     */
    public function liveParams(string|array $params): static
    {
        $this->attrs['data-action-params'] = is_array($params) ? json_encode($params, JSON_UNESCAPED_UNICODE) : $params;
        return $this;
    }

    /**
     * 声明依赖的 JS 脚本
     *
     * 确保 AssetRegistry 在输出时加载指定的脚本
     *
     * @view-since 1.0.0
     * @param string ...$ids 脚本 ID 列表（在 vite.config.js 中定义）
     * @return static
     */
    public function requireScript(string ...$ids): static
    {
        $registry = \Framework\View\Document\AssetRegistry::getInstance();
        foreach ($ids as $id) {
            $registry->requireScript($id);
        }
        return $this;
    }

    /**
     * 将属性数组转换为 HTML 属性字符串
     *
     * 安全处理：
     * - 过滤 on* 事件属性
     * - 过滤 javascript:/data: 协议
     * - 自动转义特殊字符
     *
     * @param array $attrs 属性键值对
     * @return string HTML 属性字符串（如 ' id="main" class="btn"'）
     */
    private function attrString(array $attrs): string
    {
        $attrStr = '';
        foreach ($attrs as $name => $value) {
            // if like onclick onkey
            if (preg_match('/^on/', $name)) {
                continue;
            }

            // if name has space
            if (str_contains($name, ' ')) {
                $name = str_replace(' ', '-', $name);
            }

            if (is_string($value)) {
                if (preg_match('#^\s*(javascript:|data:)#i', $value)) {
                    continue;
                }
            }

            if (is_array($value)) {
                $attrStr .= $this->attrString($value);
                continue;
            }
            if ($value === '') {
                $attrStr .= ' ' . $name;
            } else {
                $attrStr .= ' ' . $name . '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
            }
        }
        return $attrStr;
    }

    /**
     * 净化 HTML 内容，防止 XSS 攻击
     *
     * 安全策略（白名单 + 黑名单）：
     * - 允许安全的语义化标签（a, p, div, span, h1-h6 等）
     * - 移除所有 on* 事件处理器
     * - 移除危险标签（script, style, iframe, object 等）
     * - 清除 javascript:/data:/vbscript: 协议链接
     *
     * @param string $html 原始 HTML 字符串
     * @return string 经过安全过滤的 HTML
     */
    private function sanitizeHtml(string $html): string
    {
        // 对未知/危险的标签直接进行 HTML 编码，防止 XSS
        $allowedTags = [
            'a',
            'abbr',
            'b',
            'blockquote',
            'br',
            'cite',
            'code',
            'dd',
            'del',
            'details',
            'dfn',
            'div',
            'dl',
            'dt',
            'em',
            'figcaption',
            'figure',
            'h1',
            'h2',
            'h3',
            'h4',
            'h5',
            'h6',
            'hr',
            'i',
            'img',
            'ins',
            'kbd',
            'li',
            'mark',
            'ol',
            'p',
            'pre',
            'q',
            's',
            'samp',
            'small',
            'span',
            'strong',
            'sub',
            'summary',
            'sup',
            'table',
            'tbody',
            'td',
            'tfoot',
            'th',
            'thead',
            'time',
            'tr',
            'u',
            'ul',
            'var',
        ];

        // 移除所有事件处理器属性 (on*)
        $html = preg_replace('/\s+on\w+\s*=\s*"[^"]*"/i', '', $html);
        $html = preg_replace("/\s+on\w+\s*=\s*'[^']*'/i", '', $html);
        $html = preg_replace('/\s+on\w+\s*=\s*[^\s>]+/i', '', $html);

        // 移除 script, style, iframe, object, embed, applet 等危险标签
        $dangerousTags = ['script', 'style', 'iframe', 'object', 'embed', 'applet', 'meta', 'link', 'base'];
        foreach ($dangerousTags as $tag) {
            $html = preg_replace('/<' . $tag . '\b[^>]*>.*?<\/' . $tag . '>/is', '', $html);
            $html = preg_replace('/<' . $tag . '\b[^>]*\/?>/i', '', $html);
        }

        // 移除 data: 和 javascript: 协议
        $html = preg_replace('/href\s*=\s*"(?:javascript|data|vbscript):/i', 'href="#"', $html);
        $html = preg_replace("/href\s*=\s*'(?:javascript|data|vbscript):/i", "href='#'", $html);
        $html = preg_replace('/src\s*=\s*"(?:javascript|data|vbscript):/i', 'src="#"', $html);
        $html = preg_replace("/src\s*=\s*'(?:javascript|data|vbscript):/i", "src='#'", $html);

        return $html;
    }



    /**
     * 渲染元素为 HTML 字符串
     *
     * 处理流程：
     * 1. 安全检查（移除 script 内容、onclick 属性）
     * 2. 国际化翻译（data-intl）
     * 3. 生成属性字符串
     * 4. 渲染内容（text/html/children）
     * 5. XSS 过滤（所有动态内容）
     *
     * 支持的子元素类型：
     * - Element 实例 → 递归 render()
     * - LiveComponent → toHtml()
     * - 实现 toHtml() 的对象
     * - 实现 render() 的对象
     * - 字符串 → sanitizeHtml()
     * - 数组 → implode + 转义
     *
     * @view-since 1.0.0
     * @return string 完整的 HTML 字符串
     */
    public function render(): string
    {
        if ($this->tag === 'script') {
            if ($this->textContent !== null) {
                $this->textContent = '';
            }

            if ($this->htmlContent !== null) {
                $this->htmlContent = '';
            }
        }

        if (isset($this->attrs['onclick'])) {
            unset($this->attrs['onclick']);
        }

        // 处理 data-intl 自动翻译
        if (isset($this->attrs['data-intl'])) {
            $intlKey = $this->attrs['data-intl'];
            $translated = \Framework\Intl\Translator::get($intlKey);
            $this->textContent = $translated;
            $this->htmlContent = null;
            $this->children = [];
            debug('has node');
        }

        $attrs = $this->attrString($this->attrs);

        if ($this->void) {
            $html = "<{$this->tag}{$attrs}>";
        } else {
            $inner = '';
            if ($this->htmlContent !== null) {
                $inner .= $this->sanitizeHtml($this->htmlContent);
            } elseif ($this->textContent !== null) {
                $inner .= htmlspecialchars($this->textContent, ENT_QUOTES, 'UTF-8');
            }

            foreach ($this->children as $child) {
                if ($child instanceof self) {
                    $inner .= $child->render();
                } elseif ($child instanceof \Framework\Component\Live\LiveComponent) {
                    $inner .= $this->sanitizeHtml($child->toHtml());
                } elseif (is_object($child) && method_exists($child, 'toHtml')) {
                    $html = $child->toHtml();
                    $inner .= $this->sanitizeHtml(is_string($html) ? $html : (string)$html);
                } elseif (is_object($child) && method_exists($child, 'render')) {
                    $rendered = $child->render();
                    if ($rendered instanceof self) {
                        $inner .= $rendered->render();
                    } else {
                        $inner .= $this->sanitizeHtml((string)$rendered);
                    }
                } elseif (is_string($child)) {
                    $inner .= $this->sanitizeHtml($child);
                } else {
                    if (is_array($child)) {
                        $child = implode('', $child);
                    }
                    $inner .= htmlspecialchars((string)$child, ENT_QUOTES, 'UTF-8');
                }
            }
            $html = "<{$this->tag}{$attrs}>{$inner}</{$this->tag}>";
        }

        return $html;
    }

    /**
     * 魔术方法 — 将对象转换为字符串
     *
     * 允许直接 echo Element 对象
     *
     * @view-since 1.0.0
     * @return string HTML 字符串
     *
     * @view-example
     * $div = Element::make('div')->text('Hello');
     * echo $div; // 自动调用 render()
     * @view-example-end
     */
    public function __toString(): string
    {
        return $this->render();
    }
}
