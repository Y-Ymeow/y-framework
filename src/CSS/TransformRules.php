<?php

declare(strict_types=1);

namespace Framework\CSS;

class TransformRules
{
    public static function parse(string $class, ?string $pseudo = null, ?string $originalClass = null): ?string
    {
        if ($class === 'transform') return 'transform:translateX(var(--tw-translate-x,0)) translateY(var(--tw-translate-y,0)) rotate(var(--tw-rotate,0)) skewX(var(--tw-skew-x,0)) skewY(var(--tw-skew-y,0)) scaleX(var(--tw-scale-x,1)) scaleY(var(--tw-scale-y,1))';
        if ($class === 'transform-gpu') return 'transform:translate3d(var(--tw-translate-x,0),var(--tw-translate-y,0),0) rotate(var(--tw-rotate,0)) skewX(var(--tw-skew-x,0)) skewY(var(--tw-skew-y,0)) scaleX(var(--tw-scale-x,1)) scaleY(var(--tw-scale-y,1))';
        if ($class === 'transform-none') return 'transform:none';

        if (preg_match('/^translate-x-(\d+)$/', $class, $m)) {
            $v = (int)$m[1] * 0.25;
            return "--tw-translate-x:{$v}rem";
        }
        if (preg_match('/^translate-y-(\d+)$/', $class, $m)) {
            $v = (int)$m[1] * 0.25;
            return "--tw-translate-y:{$v}rem";
        }
        if ($class === 'translate-y-full') return '--tw-translate-y:100%';
        if ($class === 'translate-x-full') return '--tw-translate-x:100%';

        if (preg_match('/^rotate-(\d+)$/', $class, $m)) {
            return "--tw-rotate:{$m[1]}deg";
        }

        if (preg_match('/^scale-(\d+)$/', $class, $m)) {
            $v = (int)$m[1] / 100;
            return "--tw-scale-x:{$v};--tw-scale-y:{$v}";
        }
        if (preg_match('/^scale-x-(\d+)$/', $class, $m)) {
            $v = (int)$m[1] / 100;
            return "--tw-scale-x:{$v}";
        }
        if (preg_match('/^scale-y-(\d+)$/', $class, $m)) {
            $v = (int)$m[1] / 100;
            return "--tw-scale-y:{$v}";
        }

        if ($class === 'origin-center') return 'transform-origin:center';
        if ($class === 'origin-top') return 'transform-origin:top';
        if ($class === 'origin-top-right') return 'transform-origin:top right';
        if ($class === 'origin-right') return 'transform-origin:right';
        if ($class === 'origin-bottom-right') return 'transform-origin:bottom right';
        if ($class === 'origin-bottom') return 'transform-origin:bottom';
        if ($class === 'origin-bottom-left') return 'transform-origin:bottom left';
        if ($class === 'origin-left') return 'transform-origin:left';
        if ($class === 'origin-top-left') return 'transform-origin:top left';

        return null;
    }
}
