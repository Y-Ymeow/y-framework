<?php

declare(strict_types=1);

namespace Framework\View\Element;

use Framework\View\Base\Element;
use Framework\View\Concerns\HasTailwindSpacing;
use Framework\View\Concerns\HasTailwindLayout;
use Framework\View\Concerns\HasTailwindAppearance;

class Container extends Element
{
    use HasTailwindSpacing;
    use HasTailwindLayout;
    use HasTailwindAppearance;

    public function __construct(?string $tag = 'div')
    {
        $tag = $tag ?? 'div';
        parent::__construct($tag);
    }

    public static function section(): static { return new static('section'); }
    public static function nav(): static { return new static('nav'); }
    public static function article(): static { return new static('article'); }
    public static function aside(): static { return new static('aside'); }
    public static function main(): static { return new static('main'); }
    public static function header(): static { return new static('header'); }
    public static function footer(): static { return new static('footer'); }
}
