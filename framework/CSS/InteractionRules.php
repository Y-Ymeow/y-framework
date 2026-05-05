<?php

declare(strict_types=1);

namespace Framework\CSS;

class InteractionRules
{
    public static function parse(string $class, ?string $pseudo = null, ?string $originalClass = null): ?string
    {
        if ($class === 'cursor-auto') return 'cursor:auto';
        if ($class === 'cursor-default') return 'cursor:default';
        if ($class === 'cursor-pointer') return 'cursor:pointer';
        if ($class === 'cursor-wait') return 'cursor:wait';
        if ($class === 'cursor-text') return 'cursor:text';
        if ($class === 'cursor-move') return 'cursor:move';
        if ($class === 'cursor-help') return 'cursor:help';
        if ($class === 'cursor-not-allowed') return 'cursor:not-allowed';
        if ($class === 'cursor-none') return 'cursor:none';
        if ($class === 'cursor-grab') return 'cursor:grab';
        if ($class === 'cursor-grabbing') return 'cursor:grabbing';

        if ($class === 'pointer-events-none') return 'pointer-events:none';
        if ($class === 'pointer-events-auto') return 'pointer-events:auto';

        if ($class === 'resize-none') return 'resize:none';
        if ($class === 'resize') return 'resize:both';
        if ($class === 'resize-y') return 'resize:vertical';
        if ($class === 'resize-x') return 'resize:horizontal';

        if ($class === 'select-none') return 'user-select:none';
        if ($class === 'select-text') return 'user-select:text';
        if ($class === 'select-all') return 'user-select:all';
        if ($class === 'select-auto') return 'user-select:auto';

        if ($class === 'outline-none') return 'outline:none';

        if ($class === 'sr-only') return 'position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border-width:0';
        if ($class === 'not-sr-only') return 'position:static;width:auto;height:auto;padding:0;margin:0;overflow:visible;clip:auto;white-space:normal';

        if ($class === 'ring-0') return 'box-shadow:0 0 0 0';
        if ($class === 'ring-1') return 'box-shadow:0 0 0 1px';
        if ($class === 'ring-2') return 'box-shadow:0 0 0 2px';
        if ($class === 'ring-4') return 'box-shadow:0 0 0 4px';
        if ($class === 'ring-8') return 'box-shadow:0 0 0 8px';
        if ($class === 'ring-inset') return 'box-shadow:inset 0 0 0 1px';

        return null;
    }
}
