<?php

declare(strict_types=1);

namespace Framework\View\Element;

use Framework\View\Base\Element;
use Framework\View\Concerns\HasTailwindTypography;

class Text extends Element
{
    use HasTailwindTypography;

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

    public static function h1(string|Element|null $content = null): static { return new static('h1', $content); }
    public static function h2(string|Element|null $content = null): static { return new static('h2', $content); }
    public static function h3(string|Element|null $content = null): static { return new static('h3', $content); }
    public static function h4(string|Element|null $content = null): static { return new static('h4', $content); }
    public static function h5(string|Element|null $content = null): static { return new static('h5', $content); }
    public static function h6(string|Element|null $content = null): static { return new static('h6', $content); }
    public static function p(string|Element|null $content = null): static { return new static('p', $content); }
    public static function strong(string|Element|null $content = null): static { return new static('strong', $content); }
    public static function em(string|Element|null $content = null): static { return new static('em', $content); }
    public static function small(string|Element|null $content = null): static { return new static('small', $content); }
    public static function blockquote(string|Element|null $content = null): static { return new static('blockquote', $content); }
    public static function code(string|Element|null $content = null): static { return new static('code', $content); }
    public static function pre(string|Element|null $content = null): static { return new static('pre', $content); }
}
