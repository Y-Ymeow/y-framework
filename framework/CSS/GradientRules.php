<?php

declare(strict_types=1);

namespace Framework\CSS;

class GradientRules
{
    public static function parse(string $class, ?string $pseudo = null, ?string $originalClass = null): ?string
    {
        if (preg_match('/^bg-gradient-to-(r|l|t|b|tr|tl|br|bl)$/', $class, $m)) {
            $direction = match($m[1]) {
                'r' => 'to right',
                'l' => 'to left',
                't' => 'to top',
                'b' => 'to bottom',
                'tr' => 'to top right',
                'tl' => 'to top left',
                'br' => 'to bottom right',
                'bl' => 'to bottom left',
            };
            return "background-image:linear-gradient({$direction}, var(--tw-gradient-from, currentColor), var(--tw-gradient-to, currentColor))";
        }

        if (preg_match('/^from-(.+)$/', $class, $m)) {
            $color = ColorRules::getColor($m[1]);
            if ($color) {
                return "--tw-gradient-from:{$color}";
            }
        }

        if (preg_match('/^to-(.+)$/', $class, $m)) {
            $color = ColorRules::getColor($m[1]);
            if ($color) {
                return "--tw-gradient-to:{$color}";
            }
        }

        if (preg_match('/^via-(.+)$/', $class, $m)) {
            $color = ColorRules::getColor($m[1]);
            if ($color) {
                return "--tw-gradient-via:{$color}";
            }
        }

        return null;
    }
}
