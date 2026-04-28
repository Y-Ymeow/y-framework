<?php

declare(strict_types=1);

namespace Framework\CSS;

class CSSEngine
{
    private static array $breakpoints = [
        'sm' => '(min-width: 640px)',
        'md' => '(min-width: 768px)',
        'lg' => '(min-width: 1024px)',
        'xl' => '(min-width: 1280px)',
        '2xl' => '(min-width: 1536px)',
        'max-sm' => '(max-width: 639px)',
        'max-md' => '(max-width: 767px)',
        'max-lg' => '(max-width: 1023px)',
        'max-xl' => '(max-width: 1279px)',
    ];

    private static array $pseudoClasses = [
        'hover' => ':hover',
        'focus' => ':focus',
        'active' => ':active',
        'visited' => ':visited',
        'first' => ':first-child',
        'last' => ':last-child',
        'odd' => ':nth-child(odd)',
        'even' => ':nth-child(even)',
        'first-child' => ':first-child',
        'last-child' => ':last-child',
        'only-child' => ':only-child',
        'disabled' => ':disabled',
        'enabled' => ':enabled',
        'checked' => ':checked',
        'placeholder' => '::placeholder',
        'focus-visible' => ':focus-visible',
        'focus-within' => ':focus-within',
        'after' => '::after',
        'before' => '::before',
        'selection' => '::selection',
    ];

    private static array $pseudoColorPrefixes = [
        'placeholder' => 'color',
    ];

    public static array $alwaysGenerated = [
        'opacity-0', 'opacity-100', 'scale-90', 'scale-95', 'scale-100',
        'translate-y-full', 'translate-x-full', 'translate-y-0', 'translate-x-0',
        'flex-row', 'flex-row-reverse', 'flex-col', 'flex-col-reverse',
        'flex-wrap', 'flex-wrap-reverse', 'flex-nowrap',
        'justify-start', 'justify-end', 'justify-center', 'justify-between', 'justify-around', 'justify-evenly',
        'items-start', 'items-end', 'items-center', 'items-baseline', 'items-stretch',
        'flex', 'inline-flex', 'grid', 'block', 'hidden',
        'gap-0', 'gap-1', 'gap-2', 'gap-3', 'gap-4', 'gap-6', 'gap-8', 'gap-10', 'gap-12',
        'p-0', 'p-1', 'p-2', 'p-3', 'p-4', 'p-6', 'p-8', 'p-10', 'p-12',
        'm-0', 'm-1', 'm-2', 'm-3', 'm-4', 'm-6', 'm-8', 'm-10', 'm-12',
        'mb-0', 'mb-1', 'mb-2', 'mb-3', 'mb-4', 'mb-6', 'mb-8', 'mb-10', 'mb-12',
        'mt-0', 'mt-1', 'mt-2', 'mt-3', 'mt-4', 'mt-6', 'mt-8', 'mt-10', 'mt-12',
        'mr-0', 'mr-1', 'mr-2', 'mr-3', 'mr-4', 'mr-6', 'mr-8', 'mr-10', 'mr-12',
        'ml-0', 'ml-1', 'ml-2', 'ml-3', 'ml-4', 'ml-6', 'ml-8', 'ml-10', 'ml-12',
        'mx-0', 'mx-1', 'mx-2', 'mx-3', 'mx-4', 'mx-6', 'mx-8', 'mx-10', 'mx-12',
        'my-0', 'my-1', 'my-2', 'my-3', 'my-4', 'my-6', 'my-8', 'my-10', 'my-12',
        'min-h-screen', 'min-h-full',
        'overflow-auto', 'overflow-hidden', 'overflow-visible', 'overflow-scroll',
        'overflow-x-auto', 'overflow-y-auto',
        'border', 'border-t', 'border-r', 'border-b', 'border-l',
        'bg-white', 'bg-gray-50', 'bg-gray-100', 'bg-blue-100', 'bg-green-100',
        'rounded', 'rounded-sm', 'rounded-md', 'rounded-lg', 'rounded-full',
        'max-w-lg', 'max-w-md', 'max-w-sm', 'max-w-xs',
        'w-full', 'h-full',
    ];

    public static function parseClass(string $class): array
    {
        $isImportant = false;
        $workingClass = $class;
        if (str_ends_with($class, '!')) {
            $isImportant = true;
            $workingClass = substr($class, 0, -1);
        }

        $parts = explode(':', $workingClass);
        $mediaQuery = null;
        $pseudoSelector = '';
        $utilityFound = false;
        $utilityParts = [];

        foreach ($parts as $i => $part) {
            if ($i === 0 && !isset(self::$breakpoints[$part]) && !isset(self::$pseudoClasses[$part])) {
                $utilityFound = true;
                $utilityParts[] = $part;
                continue;
            }

            if (isset(self::$breakpoints[$part])) {
                $mediaQuery = self::$breakpoints[$part];
            } elseif (isset(self::$pseudoClasses[$part])) {
                $pseudoSelector .= self::$pseudoClasses[$part];
            } elseif ($utilityFound) {
                $utilityParts[] = $part;
            } else {
                $utilityFound = true;
                $utilityParts[] = $part;
            }
        }

        $baseClass = !empty($utilityParts) ? implode(':', $utilityParts) : $workingClass;

        $dashPseudo = self::extractDashPseudo($baseClass);
        if ($dashPseudo !== null) {
            $baseClass = $dashPseudo['base'];
            if ($pseudoSelector) {
                $pseudoSelector .= $dashPseudo['pseudo'];
            } else {
                $pseudoSelector = $dashPseudo['pseudo'];
            }
        }

        return [
            'base' => $baseClass,
            'media' => $mediaQuery,
            'pseudo' => $pseudoSelector,
            'original' => $class,
            'important' => $isImportant,
            'hasBreakpoint' => $mediaQuery !== null,
            'hasPseudo' => $pseudoSelector !== '',
        ];
    }

    private static function extractDashPseudo(string $baseClass): ?array
    {
        $dashPseudoMap = [
            'placeholder' => '::placeholder',
        ];

        foreach ($dashPseudoMap as $prefix => $pseudo) {
            if (str_starts_with($baseClass, $prefix . '-')) {
                $rest = substr($baseClass, strlen($prefix) + 1);
                return ['base' => $rest, 'pseudo' => $pseudo];
            }
        }
        return null;
    }

    public static function buildSelector(string $originalClass, string $pseudo = ''): string
    {
        $escaped = preg_replace('/([^a-zA-Z0-9_-])/', '\\\\$1', $originalClass);
        return '.' . $escaped . $pseudo;
    }

    public static function buildRule(string $selector, string $properties, ?string $mediaQuery = null): string
    {
        $rule = $selector . '{' . $properties . '}';
        if ($mediaQuery) {
            $rule = "@media {$mediaQuery} {{$rule}}";
        }
        return $rule;
    }

    public static function buildMediaQuery(string $rule, string $mediaQuery): string
    {
        return "@media {$mediaQuery} {{$rule}}";
    }

    public static function getBreakpoints(): array
    {
        return self::$breakpoints;
    }

    public static function getPseudoClasses(): array
    {
        return self::$pseudoClasses;
    }

    public static function isBreakpoint(string $part): bool
    {
        return isset(self::$breakpoints[$part]);
    }

    public static function isPseudoClass(string $part): bool
    {
        return isset(self::$pseudoClasses[$part]);
    }
}
