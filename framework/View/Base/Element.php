<?php

declare(strict_types=1);

namespace Framework\View\Base;

use Framework\View\Concerns\HasLiveDirectives;
use Framework\View\Concerns\HasBindDirectives;
use Framework\View\Concerns\HasVisibility;

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
    use HasLiveDirectives;
    use HasBindDirectives;
    use HasVisibility;

    protected string $tag;
    protected array $attrs = [];
    protected array $children = [];
    protected ?string $htmlContent = null;
    protected ?string $textContent = null;
    protected bool $void = false;
    protected bool $isComment = false;

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
     * 将元素标记为 HTML 注释节点
     *
     * 调用后，render() 会输出 HTML 注释而非实际标签。
     * 用于需要注册资源（JS/CSS）但不想输出可见 DOM 的场景，
     * 如 Toast、Chart 等纯 JS 驱动的组件。
     *
     * @view-since 1.0.0
     * @param string $comment 注释内容（可选）
     * @return static
     *
     * @view-example
     * // Toast 组件只注册 JS，不输出可见元素
     * return Element::make('div')
     *     ->comment('toast placeholder')
     *     ->requireScript('ux:toast');
     * // 输出: <!-- toast placeholder -->
     * @view-example-end
     */
    public function comment(string $comment = ''): static
    {
        $this->isComment = true;
        if ($comment !== '') {
            $this->textContent = $comment;
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
     * @view-internal
     * @param array $attrs 属性键值对
     * @return string HTML 属性字符串（如 ' id="main" class="btn"'）
     */
    private function attrString(array $attrs): string
    {
        $attrStr = '';
        foreach ($attrs as $name => $value) {
            if (preg_match('/^on/', $name)) {
                continue;
            }

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
     * @view-internal
     * @param string $html 原始 HTML 字符串
     * @return string 经过安全过滤的 HTML
     */
    private function sanitizeHtml(string $html): string
    {
        $allowedTags = [
            'a', 'abbr', 'b', 'blockquote', 'br', 'cite', 'code', 'dd', 'del',
            'details', 'dfn', 'div', 'dl', 'dt', 'em', 'figcaption', 'figure',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'i', 'img', 'ins', 'kbd',
            'li', 'mark', 'ol', 'p', 'pre', 'q', 's', 'samp', 'small', 'span',
            'strong', 'sub', 'summary', 'sup', 'table', 'tbody', 'td', 'tfoot',
            'th', 'thead', 'time', 'tr', 'u', 'ul', 'var',
        ];

        $html = preg_replace('/\s+on\w+\s*=\s*"[^"]*"/i', '', $html);
        $html = preg_replace("/\s+on\w+\s*=\s*'[^']*'/i", '', $html);
        $html = preg_replace('/\s+on\w+\s*=\s*[^\s>]+/i', '', $html);

        $dangerousTags = ['script', 'style', 'iframe', 'object', 'embed', 'applet', 'meta', 'link', 'base'];
        foreach ($dangerousTags as $tag) {
            $html = preg_replace('/<' . $tag . '\b[^>]*>.*?<\/' . $tag . '>/is', '', $html);
            $html = preg_replace('/<' . $tag . '\b[^>]*\/?>/i', '', $html);
        }

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
        if ($this->isComment) {
            $comment = $this->textContent ?? '';
            return $comment !== '' ? "<!-- {$comment} -->" : '<!-- -->';
        }

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

        if (isset($this->attrs['data-intl'])) {
            $intlKey = $this->attrs['data-intl'];
            $translated = \Framework\Intl\Translator::get($intlKey);
            $this->textContent = $translated;
            $this->htmlContent = null;
            $this->children = [];
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
