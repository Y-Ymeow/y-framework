<?php

declare(strict_types=1);

namespace Framework\CSS;

class TypographyRules
{
    private static array $fontSizes = [
        'xs' => '0.75rem',
        'sm' => '0.875rem',
        'base' => '1rem',
        'lg' => '1.125rem',
        'xl' => '1.25rem',
        '2xl' => '1.5rem',
        '3xl' => '1.875rem',
        '4xl' => '2.25rem',
        '5xl' => '3rem',
        '6xl' => '3.75rem',
        '7xl' => '4.5rem',
        '8xl' => '6rem',
        '9xl' => '8rem',
    ];

    private static array $fontWeights = [
        'thin' => 100,
        'extralight' => 200,
        'light' => 300,
        'normal' => 400,
        'medium' => 500,
        'semibold' => 600,
        'bold' => 700,
        'extrabold' => 800,
        'black' => 900,
    ];

    private static array $lineHeights = [
        'none' => '1',
        'tight' => '1.25',
        'snug' => '1.375',
        'normal' => '1.5',
        'relaxed' => '1.625',
        'loose' => '2',
    ];

    private static array $fontFamilies = [
        'sans' => 'ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji"',
        'serif' => 'ui-serif, Georgia, Cambria, "Times New Roman", Times, serif',
        'mono' => 'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace',
    ];

    public static function parse(string $class, ?string $pseudo = null, ?string $originalClass = null): ?string
    {
        if (preg_match('/^text-\[(.+)\]$/', $class, $m)) {
            return "font-size:{$m[1]}";
        }
        if (preg_match('/^text-(.+)$/', $class, $m) && isset(self::$fontSizes[$m[1]])) {
            return "font-size:" . self::$fontSizes[$m[1]];
        }

        if (preg_match('/^font-size-\[(.+)\]$/', $class, $m)) {
            return "font-size:{$m[1]}";
        }
        if (preg_match('/^font-size-(.+)$/', $class, $m) && isset(self::$fontSizes[$m[1]])) {
            return "font-size:" . self::$fontSizes[$m[1]];
        }

        if (preg_match('/^font-\[(.+)\]$/', $class, $m)) {
            return "font-family:{$m[1]}";
        }
        if (preg_match('/^font-(.+)$/', $class, $m) && isset(self::$fontWeights[$m[1]])) {
            return "font-weight:" . self::$fontWeights[$m[1]];
        }
        if (isset(self::$fontWeights[$class])) {
            return "font-weight:" . self::$fontWeights[$class];
        }

        if (preg_match('/^font-(.+)$/', $class, $m) && isset(self::$fontFamilies[$m[1]])) {
            return "font-family:" . self::$fontFamilies[$m[1]];
        }

        if (preg_match('/^leading-\[(.+)\]$/', $class, $m)) {
            return "line-height:{$m[1]}";
        }
        if (isset(self::$lineHeights[$class])) {
            return "line-height:" . self::$lineHeights[$class];
        }
        if (preg_match('/^leading-(.+)$/', $class, $m) && isset(self::$lineHeights[$m[1]])) {
            return "line-height:" . self::$lineHeights[$m[1]];
        }

        if (preg_match('/^tracking-\[(.+)\]$/', $class, $m)) {
            return "letter-spacing:{$m[1]}";
        }

        if ($class === 'text-left') return 'text-align:left';
        if ($class === 'text-center') return 'text-align:center';
        if ($class === 'text-right') return 'text-align:right';
        if ($class === 'text-justify') return 'text-align:justify';

        if ($class === 'uppercase') return 'text-transform:uppercase';
        if ($class === 'lowercase') return 'text-transform:lowercase';
        if ($class === 'capitalize') return 'text-transform:capitalize';
        if ($class === 'normal-case') return 'text-transform:none';

        if ($class === 'underline') return 'text-decoration:underline';
        if ($class === 'overline') return 'text-decoration:overline';
        if ($class === 'line-through') return 'text-decoration:line-through';
        if ($class === 'no-underline') return 'text-decoration:none';

        if ($class === 'truncate') return 'overflow:hidden;text-overflow:ellipsis;white-space:nowrap';
        if ($class === 'whitespace-normal') return 'white-space:normal';
        if ($class === 'whitespace-nowrap') return 'white-space:nowrap';
        if ($class === 'whitespace-pre') return 'white-space:pre';
        if ($class === 'whitespace-pre-wrap') return 'white-space:pre-wrap';

        if ($class === 'antialiased') return '-webkit-font-smoothing:antialiased;-moz-osx-font-smoothing:grayscale';
        if ($class === 'subpixel-antialiased') return '-webkit-font-smoothing:auto';

        if ($class === 'italic') return 'font-style:italic';
        if ($class === 'not-italic') return 'font-style:normal';

        if ($class === 'tracking-tighter') return 'letter-spacing:-0.05em';
        if ($class === 'tracking-tight') return 'letter-spacing:-0.025em';
        if ($class === 'tracking-normal') return 'letter-spacing:0em';
        if ($class === 'tracking-wide') return 'letter-spacing:0.025em';
        if ($class === 'tracking-wider') return 'letter-spacing:0.05em';
        if ($class === 'tracking-widest') return 'letter-spacing:0.1em';

        return null;
    }
}
