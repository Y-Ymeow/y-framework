<?php

declare(strict_types=1);

namespace Framework\CSS;

class TransitionRules
{
    public static function parse(string $class, ?string $pseudo = null, ?string $originalClass = null): ?string
    {
        if ($class === 'transition-none') return 'transition-property:none';
        if ($class === 'transition-all') return 'transition-property:all;transition-timing-function:cubic-bezier(0.4,0,0.2,1);transition-duration:150ms';
        if ($class === 'transition') return 'transition-property:background-color,border-color,color,fill,stroke,opacity,box-shadow,transform,filter,backdrop-filter;transition-timing-function:cubic-bezier(0.4,0,0.2,1);transition-duration:150ms';
        if ($class === 'transition-colors') return 'transition-property:background-color,border-color,color,fill,stroke;transition-duration:150ms';
        if ($class === 'transition-opacity') return 'transition-property:opacity;transition-duration:150ms';
        if ($class === 'transition-shadow') return 'transition-property:box-shadow;transition-duration:150ms';
        if ($class === 'transition-transform') return 'transition-property:transform;transition-duration:150ms';

        if (preg_match('/^duration-(\d+)$/', $class, $m)) {
            return "transition-duration:{$m[1]}ms";
        }

        if ($class === 'ease-linear') return 'transition-timing-function:linear';
        if ($class === 'ease-in') return 'transition-timing-function:cubic-bezier(0.4,0,1,1)';
        if ($class === 'ease-out') return 'transition-timing-function:cubic-bezier(0,0,0.2,1)';
        if ($class === 'ease-in-out') return 'transition-timing-function:cubic-bezier(0.4,0,0.2,1)';

        if (preg_match('/^delay-(\d+)$/', $class, $m)) {
            return "transition-delay:{$m[1]}ms";
        }

        return null;
    }
}
