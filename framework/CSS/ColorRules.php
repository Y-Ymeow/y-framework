<?php

declare(strict_types=1);

namespace Framework\CSS;

class ColorRules
{
    private static array $colors = [
        'black' => '#000',
        'white' => '#fff',
        'transparent' => 'transparent',
        'gray-50' => '#f9fafb',
        'gray-100' => '#f3f4f6',
        'gray-200' => '#e5e7eb',
        'gray-300' => '#d1d5db',
        'gray-400' => '#9ca3af',
        'gray-500' => '#6b7280',
        'gray-600' => '#4b5563',
        'gray-700' => '#374151',
        'gray-800' => '#1f2937',
        'gray-900' => '#111827',
        'red-50' => '#fef2f2',
        'red-100' => '#fee2e2',
        'red-200' => '#fecaca',
        'red-300' => '#fca5a5',
        'red-400' => '#f87171',
        'red-500' => '#ef4444',
        'red-600' => '#dc2626',
        'red-700' => '#b91c1c',
        'red-800' => '#991b1b',
        'red-900' => '#7f1d1d',
        'green-50' => '#f0fdf4',
        'green-100' => '#dcfce7',
        'green-200' => '#bbf7d0',
        'green-300' => '#86efac',
        'green-400' => '#4ade80',
        'green-500' => '#22c55e',
        'green-600' => '#16a34a',
        'green-700' => '#15803d',
        'green-800' => '#166534',
        'green-900' => '#14532d',
        'blue-50' => '#eff6ff',
        'blue-100' => '#dbeafe',
        'blue-200' => '#bfdbfe',
        'blue-300' => '#93c5fd',
        'blue-400' => '#60a5fa',
        'blue-500' => '#3b82f6',
        'blue-600' => '#2563eb',
        'blue-700' => '#1d4ed8',
        'blue-800' => '#1e40af',
        'blue-900' => '#1e3a8a',
        'yellow-50' => '#fefce8',
        'yellow-100' => '#fef9c3',
        'yellow-200' => '#fef08a',
        'yellow-300' => '#fde047',
        'yellow-400' => '#facc15',
        'yellow-500' => '#eab308',
        'yellow-600' => '#ca8a04',
        'orange-50' => '#fff7ed',
        'orange-100' => '#ffedd5',
        'orange-200' => '#fed7aa',
        'orange-300' => '#fdba74',
        'orange-400' => '#fb923c',
        'orange-500' => '#f97316',
        'orange-600' => '#ea580c',
        'purple-50' => '#faf5ff',
        'purple-100' => '#f3e8ff',
        'purple-200' => '#e9d5ff',
        'purple-300' => '#d8b4fe',
        'purple-400' => '#c084fc',
        'purple-500' => '#a855f7',
        'purple-600' => '#9333ea',
        'indigo-50' => '#eef2ff',
        'indigo-100' => '#e0e7ff',
        'indigo-200' => '#c7d2fe',
        'indigo-300' => '#a5b4fc',
        'indigo-400' => '#818cf8',
        'indigo-500' => '#6366f1',
        'indigo-600' => '#4f46e5',
        'pink-50' => '#fdf2f8',
        'pink-100' => '#fce7f3',
        'pink-200' => '#fbcfe8',
        'pink-300' => '#f9a8d4',
        'pink-400' => '#f472b6',
        'pink-500' => '#ec4899',
        'pink-600' => '#db2777',
        'rose-50' => '#fff1f2',
        'rose-100' => '#ffe4e6',
        'rose-200' => '#fecdd3',
        'rose-300' => '#fda4af',
        'rose-400' => '#fb7185',
        'rose-500' => '#f43f5e',
        'rose-600' => '#e11d48',
        'rose-700' => '#be123c',
        'rose-800' => '#9f1239',
        'rose-900' => '#881337',
        'cyan-50' => '#ecfeff',
        'cyan-100' => '#cffafe',
        'cyan-200' => '#a5f3fc',
        'cyan-300' => '#67e8f9',
        'cyan-400' => '#22d3ee',
        'cyan-500' => '#06b6d4',
        'cyan-600' => '#0891b2',
        'cyan-700' => '#0e7490',
        'cyan-800' => '#155e75',
        'cyan-900' => '#164e63',
        'emerald-50' => '#ecfdf5',
        'emerald-100' => '#d1fae5',
        'emerald-200' => '#a7f3d0',
        'emerald-300' => '#6ee7b7',
        'emerald-400' => '#34d399',
        'emerald-500' => '#10b981',
        'emerald-600' => '#059669',
        'emerald-700' => '#047857',
        'emerald-800' => '#065f46',
        'emerald-900' => '#064e3b',
        'teal-50' => '#f0fdfa',
        'teal-100' => '#ccfbf1',
        'teal-200' => '#99f6e4',
        'teal-300' => '#5eead4',
        'teal-400' => '#2dd4bf',
        'teal-500' => '#14b8a6',
        'teal-600' => '#0d9488',
        'teal-700' => '#0f766e',
        'teal-800' => '#115e59',
        'teal-900' => '#134e4a',
        'slate-50' => '#f8fafc',
        'slate-100' => '#f1f5f9',
        'slate-200' => '#e2e8f0',
        'slate-300' => '#cbd5e1',
        'slate-400' => '#94a3b8',
        'slate-500' => '#64748b',
        'slate-600' => '#475569',
        'slate-700' => '#334155',
        'slate-800' => '#1e293b',
        'slate-900' => '#0f172a',
        'amber-50' => '#fffbeb',
        'amber-100' => '#fef3c7',
        'amber-200' => '#fde68a',
        'amber-300' => '#fcd34d',
        'amber-400' => '#fbbf24',
        'amber-500' => '#f59e0b',
        'amber-600' => '#d97706',
        'amber-700' => '#b45309',
        'amber-800' => '#92400e',
        'amber-900' => '#78350f',
    ];

    public static function getColor(string $name): ?string
    {
        return self::$colors[$name] ?? null;
    }

    public static function parse(string $class, ?string $pseudo = null, ?string $originalClass = null): ?string
    {
        if (preg_match('/^text-\[(.+)\]$/', $class, $m)) {
            return "color:{$m[1]}";
        }
        if (preg_match('/^text-(.+)$/', $class, $m) && isset(self::$colors[$m[1]])) {
            return "color:" . self::$colors[$m[1]];
        }
        if (preg_match('/^bg-\[(.+)\]$/', $class, $m)) {
            return "background-color:{$m[1]}";
        }
        if (preg_match('/^bg-(.+)$/', $class, $m) && isset(self::$colors[$m[1]])) {
            return "background-color:" . self::$colors[$m[1]];
        }
        if (preg_match('/^border-\[(.+)\]$/', $class, $m)) {
            return "border-color:{$m[1]}";
        }
        if (preg_match('/^border-(.+)$/', $class, $m) && isset(self::$colors[$m[1]])) {
            return "border-color:" . self::$colors[$m[1]];
        }

        if ($pseudo !== null && isset(self::$colors[$class])) {
            $pseudoColorProps = [
                '::placeholder' => 'color',
            ];

            if (isset($pseudoColorProps[$pseudo])) {
                return $pseudoColorProps[$pseudo] . ":" . self::$colors[$class];
            }

            if ($pseudo === ':hover' || $pseudo === ':focus' || $pseudo === ':active') {
                if (preg_match('/^(text|bg|border)-(.+)$/', $originalClass ?? '', $m)) {
                    $prop = match($m[1]) {
                        'text' => 'color',
                        'bg' => 'background-color',
                        'border' => 'border-color',
                    };
                    if (isset(self::$colors[$m[2]])) {
                        return $prop . ":" . self::$colors[$m[2]];
                    }
                }
            }
        }

        return null;
    }
}
