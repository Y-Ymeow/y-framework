<?php

declare(strict_types=1);

namespace Framework\CSS;

class UtilityRules
{
    private static array $shadows = [
        'sm' => '0 1px 2px 0 rgb(0 0 0 / 0.05)',
        'DEFAULT' => '0 1px 3px 0 rgb(0 0 0 / 0.1), 0 1px 2px -1px rgb(0 0 0 / 0.1)',
        'md' => '0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1)',
        'lg' => '0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1)',
        'xl' => '0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1)',
        '2xl' => '0 25px 50px -12px rgb(0 0 0 / 0.25)',
        'inner' => 'inset 0 2px 4px 0 rgb(0 0 0 / 0.05)',
        'none' => '0 0 #0000',
    ];

    private static array $transitions = [
        'none' => 'none',
        'all' => 'all 150ms cubic-bezier(0.4, 0, 0.2, 1)',
        'DEFAULT' => 'color 150ms cubic-bezier(0.4, 0, 0.2, 1), background-color 150ms cubic-bezier(0.4, 0, 0.2, 1), border-color 150ms cubic-bezier(0.4, 0, 0.2, 1), text-decoration-color 150ms cubic-bezier(0.4, 0, 0.2, 1), fill 150ms cubic-bezier(0.4, 0, 0.2, 1), stroke 150ms cubic-bezier(0.4, 0, 0.2, 1), opacity 150ms cubic-bezier(0.4, 0, 0.2, 1), box-shadow 150ms cubic-bezier(0.4, 0, 0.2, 1), transform 150ms cubic-bezier(0.4, 0, 0.2, 1), filter 150ms cubic-bezier(0.4, 0, 0.2, 1), backdrop-filter 150ms cubic-bezier(0.4, 0, 0.2, 1)',
        'colors' => 'color 150ms cubic-bezier(0.4, 0, 0.2, 1), background-color 150ms cubic-bezier(0.4, 0, 0.2, 1), border-color 150ms cubic-bezier(0.4, 0, 0.2, 1), text-decoration-color 150ms cubic-bezier(0.4, 0, 0.2, 1), fill 150ms cubic-bezier(0.4, 0, 0.2, 1), stroke 150ms cubic-bezier(0.4, 0, 0.2, 1)',
        'opacity' => 'opacity 150ms cubic-bezier(0.4, 0, 0.2, 1)',
        'shadow' => 'box-shadow 150ms cubic-bezier(0.4, 0, 0.2, 1)',
        'transform' => 'transform 150ms cubic-bezier(0.4, 0, 0.2, 1)',
    ];

    private static array $borderRadius = [
        'none' => '0',
        'sm' => '0.125rem',
        'DEFAULT' => '0.25rem',
        'md' => '0.375rem',
        'lg' => '0.5rem',
        'xl' => '0.75rem',
        '2xl' => '1rem',
        '3xl' => '1.5rem',
        'full' => '9999px',
    ];

    public static function parse(string $class, ?string $pseudo = null, ?string $originalClass = null): ?string
    {
        // shadow-*
        if (preg_match('/^shadow-(.+)$/', $class, $m) && isset(self::$shadows[$m[1]])) {
            return "box-shadow:" . self::$shadows[$m[1]];
        }
        if ($class === 'shadow' && isset(self::$shadows['DEFAULT'])) {
            return "box-shadow:" . self::$shadows['DEFAULT'];
        }

        // transition-*
        if (preg_match('/^transition-(.+)$/', $class, $m) && isset(self::$transitions[$m[1]])) {
            return "transition:" . self::$transitions[$m[1]];
        }
        if ($class === 'transition' && isset(self::$transitions['DEFAULT'])) {
            return "transition:" . self::$transitions['DEFAULT'];
        }

        // rounded-*
        if (preg_match('/^rounded-(.+)$/', $class, $m) && isset(self::$borderRadius[$m[1]])) {
            return "border-radius:" . self::$borderRadius[$m[1]];
        }
        if ($class === 'rounded' && isset(self::$borderRadius['DEFAULT'])) {
            return "border-radius:" . self::$borderRadius['DEFAULT'];
        }

        // space-y-*, space-x-*
        if (preg_match('/^space-y-(.+)$/', $class, $m)) {
            $val = SpacingRules::getSpacing($m[1]);
            if ($val !== null) {
                return "> * + *{margin-top:{$val}}";
            }
        }
        if (preg_match('/^space-x-(.+)$/', $class, $m)) {
            $val = SpacingRules::getSpacing($m[1]);
            if ($val !== null) {
                return "> * + *{margin-left:{$val}}";
            }
        }

        // opacity-*
        if (preg_match('/^opacity-(.+)$/', $class, $m)) {
            $val = (int)$m[1];
            if ($val >= 0 && $val <= 100) {
                return "opacity:" . ($val / 100);
            }
        }

        // min-h-*
        if (preg_match('/^min-h-(.+)$/', $class, $m)) {
            $val = match($m[1]) {
                '0' => '0',
                'full' => '100%',
                'screen' => '100vh',
                default => null,
            };
            if ($val !== null) {
                return "min-height:{$val}";
            }
        }

        // font-medium
        if ($class === 'font-medium') {
            return "font-weight:500";
        }
        if ($class === 'font-semibold') {
            return "font-weight:600";
        }

        return null;
    }
}
