<?php

declare(strict_types=1);

namespace Framework\CSS;

class EffectRules
{
    public static function parse(string $class, ?string $pseudo = null, ?string $originalClass = null): ?string
    {
        if ($class === 'shadow-xs') return 'box-shadow:0 1px 2px 0 rgba(0,0,0,0.05)';
        if ($class === 'shadow-sm') return 'box-shadow:0 1px 3px 0 rgba(0,0,0,0.1),0 1px 2px -1px rgba(0,0,0,0.1)';
        if ($class === 'shadow') return 'box-shadow:0 1px 3px 0 rgba(0,0,0,0.1),0 1px 2px -1px rgba(0,0,0,0.1)';
        if ($class === 'shadow-md') return 'box-shadow:0 4px 6px -1px rgba(0,0,0,0.1),0 2px 4px -2px rgba(0,0,0,0.1)';
        if ($class === 'shadow-lg') return 'box-shadow:0 10px 15px -3px rgba(0,0,0,0.1),0 4px 6px -4px rgba(0,0,0,0.1)';
        if ($class === 'shadow-xl') return 'box-shadow:0 20px 25px -5px rgba(0,0,0,0.1),0 8px 10px -6px rgba(0,0,0,0.1)';
        if ($class === 'shadow-2xl') return 'box-shadow:0 25px 50px -12px rgba(0,0,0,0.25)';
        if ($class === 'shadow-inner') return 'box-shadow:inset 0 2px 4px 0 rgba(0,0,0,0.05)';
        if ($class === 'shadow-none') return 'box-shadow:none';

        if (preg_match('/^opacity-(\d+)$/', $class, $m)) {
            $v = (int)$m[1];
            if ($v > 1) $v = $v / 100;
            return "opacity:{$v}";
        }

        if ($class === 'grayscale') return 'filter:grayscale(100%)';
        if ($class === 'grayscale-0') return 'filter:grayscale(0)';
        if ($class === 'sepia') return 'filter:sepia(100%)';
        if ($class === 'sepia-0') return 'filter:sepia(0)';

        if ($class === 'backdrop-blur') return 'backdrop-filter:blur(8px)';

        if ($class === 'outline-none') return 'outline:none';
        if ($class === 'outline') return 'outline:2px solid transparent;outline-offset:2px';

        return null;
    }
}
