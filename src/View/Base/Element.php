<?php

declare(strict_types=1);

namespace Framework\View\Base;

class Element
{
    protected string $tag;
    protected array $attrs = [];
    protected array $children = [];
    protected ?string $htmlContent = null;
    protected ?string $textContent = null;
    protected bool $void = false;

    public function __construct(string $tag)
    {
        $this->tag = $tag;
        $this->void = in_array($tag, ['area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr']);
    }

    public static function make(string|Element|null $tagOrcontent = null, string|Element|null $content = null): static
    {
        if (!empty($tagOrcontent) && !empty($content)) {
            return new static($tagOrcontent, $content);
        }

        return new static($tagOrcontent);
    }

    public function id(string $id): static
    {
        $this->attrs['id'] = $id;
        return $this;
    }

    public function class(string ...$classes): static
    {
        $existing = $this->attrs['class'] ?? '';
        $this->attrs['class'] = trim($existing . ' ' . implode(' ', $classes));
        return $this;
    }

    public function attr(string $name, string $value): static
    {
        if (preg_match('/^on/', $name)) {
            return $this;
        }

        $this->attrs[$name] = $value;
        return $this;
    }

    public function attrs(array $attrs): static
    {

        $this->attrs = array_merge($this->attrs, $attrs);
        return $this;
    }

    public function data(string $key, string $value): static
    {
        $this->attrs["data-{$key}"] = $value;
        return $this;
    }

    public function style(string $style): static
    {
        $this->attrs['style'] = $style;
        return $this;
    }

    public function text(?string $text = null): static
    {
        if ($text === null) return $this;
        $this->textContent = $text;
        $this->htmlContent = null;
        return $this;
    }

    public function html(mixed $html = null): static
    {
        if ($html === null) return $this;
        $this->htmlContent = (string)$html;
        $this->textContent = null;
        return $this;
    }

    public function child(mixed $child): static
    {
        $this->children[] = $child;
        return $this;
    }

    public function children(mixed ...$children): static
    {
        $this->children = array_merge($this->children, $children);
        return $this;
    }

    public function state(array $data): static
    {
        $this->attrs['data-state'] = json_encode($data, JSON_UNESCAPED_UNICODE);
        return $this;
    }

    public function model(string $name): static
    {
        $this->attrs['data-model'] = $name;
        return $this;
    }

    public function liveModel(string $name): static
    {
        $this->attrs['data-live-model'] = $name;
        $this->model($name);
        return $this;
    }

    public function bindText(string $expr): static
    {
        $this->attrs['data-text'] = $expr;
        return $this;
    }

    public function bindHtml(string $expr): static
    {
        $this->attrs['data-html'] = $expr;
        return $this;
    }

    public function bindModel(string $key): static
    {
        $this->attrs['data-model'] = $key;
        return $this;
    }

    public function bindShow(string $expr): static
    {
        $this->attrs['data-show'] = $expr;
        return $this;
    }

    public function bindTransition(string $expr): static
    {
        $this->attrs['data-transition'] = $expr;
        return $this;
    }

    public function bindIf(string $expr): static
    {
        $this->attrs['data-if'] = $expr;
        return $this;
    }

    public function bindFor(string $expr): static
    {
        $this->attrs['data-for'] = $expr;
        return $this;
    }

    public function bindOn(string $event, string $expr): static
    {
        $this->attrs["data-on:{$event}"] = $expr;
        return $this;
    }

    public function bindAttr(string $attr, string|array $expr): static
    {
        $this->attrs["data-bind:{$attr}"] = is_array($expr) ? json_encode($expr) : $expr;
        return $this;
    }

    public function dataClass(string $expr): static
    {
        $this->attrs['data-bind:class'] = $expr;
        return $this;
    }

    public function bindEffect(string $expr): static
    {
        $this->attrs['data-effect'] = $expr;
        return $this;
    }

    public function bindRef(string $name): static
    {
        $this->attrs['data-ref'] = $name;
        return $this;
    }

    public function intl(string $key): static
    {
        $this->attrs['data-intl'] = $key;
        return $this;
    }

    public function cloak(): static
    {
        $this->attrs['data-cloak'] = '';
        return $this;
    }

    public function liveFragment(string $name): static
    {
        $this->attrs['data-live-fragment'] = $name;
        \Framework\View\FragmentRegistry::record($this->attrs['data-live-fragment'], $this);
        return $this;
    }

    public function liveBind(string $key, string $type = 'text'): static
    {
        if ($type === 'text') {
            $this->attrs['data-bind'] = $key;
        } else {
            $this->attrs["data-bind-{$type}"] = $key;
        }
        return $this;
    }

    public function liveAction(string $action, string $event = 'click'): static
    {
        $this->attrs['data-action'] = $action;
        if ($event !== 'click') {
            $this->attrs['data-action-event'] = $event;
        }
        return $this;
    }

    public function liveParams(string|array $params): static
    {
        $this->attrs['data-action-params'] = is_array($params) ? json_encode($params, JSON_UNESCAPED_UNICODE) : $params;
        return $this;
    }

    /**
     * 声明当前元素依赖特定的命名的 JS 脚本
     */
    public function requireScript(string ...$ids): static
    {
        $registry = \Framework\View\Document\AssetRegistry::getInstance();
        foreach ($ids as $id) {
            $registry->requireScript($id);
        }
        return $this;
    }

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
     * 净化 HTML，移除可能造成 XSS 的标签和事件处理器
     * 白名单策略：只允许安全的 HTML 标签
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

    public function __toString(): string
    {
        return $this->render();
    }
}
