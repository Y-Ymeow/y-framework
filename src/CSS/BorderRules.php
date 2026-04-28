<?php

declare(strict_types=1);

namespace Framework\CSS;

class BorderRules
{
    public static function parse(string $class, ?string $pseudo = null, ?string $originalClass = null): ?string
    {
        if ($class === 'border') return 'border-width:1px;border-style:solid';
        if ($class === 'border-0') return 'border-width:0';
        if ($class === 'border-2') return 'border-width:2px';
        if ($class === 'border-4') return 'border-width:4px';
        if ($class === 'border-8') return 'border-width:8px';

        if ($class === 'border-t') return 'border-top-width:1px;border-style:solid';
        if ($class === 'border-t-0') return 'border-top-width:0';
        if ($class === 'border-b') return 'border-bottom-width:1px;border-style:solid';
        if ($class === 'border-b-0') return 'border-bottom-width:0';
        if ($class === 'border-l') return 'border-left-width:1px;border-style:solid';
        if ($class === 'border-l-0') return 'border-left-width:0';
        if ($class === 'border-r') return 'border-right-width:1px;border-style:solid';
        if ($class === 'border-r-0') return 'border-right-width:0';

        if ($class === 'rounded-none') return 'border-radius:0';
        if ($class === 'rounded-sm') return 'border-radius:0.125rem';
        if ($class === 'rounded') return 'border-radius:0.25rem';
        if ($class === 'rounded-md') return 'border-radius:0.375rem';
        if ($class === 'rounded-lg') return 'border-radius:0.5rem';
        if ($class === 'rounded-xl') return 'border-radius:0.75rem';
        if ($class === 'rounded-2xl') return 'border-radius:1rem';
        if ($class === 'rounded-3xl') return 'border-radius:1.5rem';
        if ($class === 'rounded-full') return 'border-radius:9999px';

        if ($class === 'rounded-t-none') return 'border-top-left-radius:0;border-top-right-radius:0';
        if ($class === 'rounded-t-sm') return 'border-top-left-radius:0.125rem;border-top-right-radius:0.125rem';
        if ($class === 'rounded-t') return 'border-top-left-radius:0.25rem;border-top-right-radius:0.25rem';
        if ($class === 'rounded-t-lg') return 'border-top-left-radius:0.5rem;border-top-right-radius:0.5rem';
        if ($class === 'rounded-t-full') return 'border-top-left-radius:9999px;border-top-right-radius:9999px';

        if ($class === 'rounded-b-none') return 'border-bottom-left-radius:0;border-bottom-right-radius:0';
        if ($class === 'rounded-b-sm') return 'border-bottom-left-radius:0.125rem;border-bottom-right-radius:0.125rem';
        if ($class === 'rounded-b') return 'border-bottom-left-radius:0.25rem;border-bottom-right-radius:0.25rem';
        if ($class === 'rounded-b-lg') return 'border-bottom-left-radius:0.5rem;border-bottom-right-radius:0.5rem';
        if ($class === 'rounded-b-full') return 'border-bottom-left-radius:9999px;border-bottom-right-radius:9999px';

        if ($class === 'rounded-l-none') return 'border-top-left-radius:0;border-bottom-left-radius:0';
        if ($class === 'rounded-l') return 'border-top-left-radius:0.25rem;border-bottom-left-radius:0.25rem';
        if ($class === 'rounded-l-lg') return 'border-top-left-radius:0.5rem;border-bottom-left-radius:0.5rem';
        if ($class === 'rounded-l-full') return 'border-top-left-radius:9999px;border-bottom-left-radius:9999px';

        if ($class === 'rounded-r-none') return 'border-top-right-radius:0;border-bottom-right-radius:0';
        if ($class === 'rounded-r') return 'border-top-right-radius:0.25rem;border-bottom-right-radius:0.25rem';
        if ($class === 'rounded-r-lg') return 'border-top-right-radius:0.5rem;border-bottom-right-radius:0.5rem';
        if ($class === 'rounded-r-full') return 'border-top-right-radius:9999px;border-bottom-right-radius:9999px';

        return null;
    }
}
