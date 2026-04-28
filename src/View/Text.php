<?php

declare(strict_types=1);

namespace Framework\View;

use Framework\View\Base\Element;

/**
 * Text — 文本元素
 *
 *   Text::make('内容')              → <span>内容</span>
 *   Text::h1('标题')               → <h1>标题</h1>
 *   Text::p('段落')                → <p>段落</p>
 *   Text::strong('加粗')           → <strong>加粗</strong>
 *   Text::em('斜体')               → <em>斜体</em>
 *   Text::small('小字')            → <small>小字</small>
 *   Text::blockquote('引用')       → <blockquote>引用</blockquote>
 *   Text::code('code')             → <code>code</code>
 *   Text::pre('preformatted')      → <pre>preformatted</pre>
 */
class Text extends Element
{
    public function __construct(?string $tag = 'span', string|Element|null $content = null)
    {
        if (!is_string($tag)) {
            $content = $tag;
            $tag = 'span';
        }

        $tag = $tag ?? 'span';
        parent::__construct($tag);
        if ($content !== null) {
            if ($content instanceof Element) {
                $this->child($content);
            } else {
                $this->text($content);
            }
        }
    }

    public static function h1(string|Element|null $content = null): static
    {
        return new static('h1', $content);
    }
    public static function h2(string|Element|null $content = null): static
    {
        return new static('h2', $content);
    }
    public static function h3(string|Element|null $content = null): static
    {
        return new static('h3', $content);
    }
    public static function h4(string|Element|null $content = null): static
    {
        return new static('h4', $content);
    }
    public static function h5(string|Element|null $content = null): static
    {
        return new static('h5', $content);
    }
    public static function h6(string|Element|null $content = null): static
    {
        return new static('h6', $content);
    }
    public static function p(string|Element|null $content = null): static
    {
        return new static('p', $content);
    }
    public static function strong(string|Element|null $content = null): static
    {
        return new static('strong', $content);
    }
    public static function em(string|Element|null $content = null): static
    {
        return new static('em', $content);
    }
    public static function small(string|Element|null $content = null): static
    {
        return new static('small', $content);
    }
    public static function blockquote(string|Element|null $content = null): static
    {
        return new static('blockquote', $content);
    }
    public static function code(string|Element|null $content = null): static
    {
        return new static('code', $content);
    }
    public static function pre(string|Element|null $content = null): static
    {
        return new static('pre', $content);
    }

    public function fontBold(): static
    {
        return $this->class('font-bold');
    }
    public function fontSemibold(): static
    {
        return $this->class('font-semibold');
    }
    public function textSm(): static
    {
        return $this->class('text-sm');
    }
    public function textXs(): static
    {
        return $this->class('text-xs');
    }
    public function textLg(): static
    {
        return $this->class('text-lg');
    }
    public function textXl(): static
    {
        return $this->class('text-xl');
    }
    public function text2xl(): static
    {
        return $this->class('text-2xl');
    }
    public function text3xl(): static
    {
        return $this->class('text-3xl');
    }
    public function textGray(string $shade = '500'): static
    {
        return $this->class("text-gray-{$shade}");
    }
    public function textWhite(): static
    {
        return $this->class('text-white');
    }
    public function textCenter(): static
    {
        return $this->class('text-center');
    }
    public function textRight(): static
    {
        return $this->class('text-right');
    }
    public function truncate(): static
    {
        return $this->class('truncate');
    }
    public function uppercase(): static
    {
        return $this->class('uppercase');
    }
    public function leading(string $size = 'normal'): static
    {
        return $this->class("leading-{$size}");
    }
}
