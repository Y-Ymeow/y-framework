<?php

declare(strict_types=1);

namespace Framework\View;

use Framework\View\Base\Element;

class Fragment extends Element
{
    public function __construct(string $name, string $tag = 'span')
    {
        parent::__construct($tag);
        $this->attr('data-live-fragment', $name);
        $this->style('display', 'contents');
    }

    /**
     * 这里的第一个参数必须兼容基类的签名，我们将其视为 Fragment 的名称
     */
    public static function make(mixed $nameOrTag = null, mixed $tag = 'span'): static
    {
        return new static((string)$nameOrTag, (string)$tag);
    }
}
