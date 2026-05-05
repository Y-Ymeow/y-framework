<?php

declare(strict_types=1);

namespace Framework\CSS;

class SpacingRules
{
    private static array $spacing = [
        '0' => '0',
        '0.5' => '0.125rem',
        '1' => '0.25rem',
        '1.5' => '0.375rem',
        '2' => '0.5rem',
        '2.5' => '0.625rem',
        '3' => '0.75rem',
        '3.5' => '0.875rem',
        '4' => '1rem',
        '5' => '1.25rem',
        '6' => '1.5rem',
        '7' => '1.75rem',
        '8' => '2rem',
        '9' => '2.25rem',
        '10' => '2.5rem',
        '11' => '2.75rem',
        '12' => '3rem',
        '14' => '3.5rem',
        '16' => '4rem',
        '20' => '5rem',
        '24' => '6rem',
        '28' => '7rem',
        '32' => '8rem',
        '36' => '9rem',
        '40' => '10rem',
        '44' => '11rem',
        '48' => '12rem',
        '52' => '13rem',
        '56' => '14rem',
        '60' => '15rem',
        '64' => '16rem',
        '72' => '18rem',
        '80' => '20rem',
        '96' => '24rem',
    ];

    public static function getSpacing(string $key): ?string
    {
        return self::$spacing[$key] ?? null;
    }

    public static function parse(string $class, ?string $pseudo = null, ?string $originalClass = null): ?string
    {
        if (preg_match('/^p-\[(.+)\]$/', $class, $m)) {
            return "padding:{$m[1]}";
        }
        if (preg_match('/^p-(\d+(?:\.\d+)?)$/', $class, $m)) {
            $v = self::calcSpacing((float)$m[1]);
            return "padding:{$v}rem";
        }
        if (preg_match('/^px-\[(.+)\]$/', $class, $m)) {
            return "padding-left:{$m[1]};padding-right:{$m[1]}";
        }
        if (preg_match('/^px-(\d+(?:\.\d+)?)$/', $class, $m)) {
            $v = self::calcSpacing((float)$m[1]);
            return "padding-left:{$v}rem;padding-right:{$v}rem";
        }
        if (preg_match('/^py-\[(.+)\]$/', $class, $m)) {
            return "padding-top:{$m[1]};padding-bottom:{$m[1]}";
        }
        if (preg_match('/^py-(\d+(?:\.\d+)?)$/', $class, $m)) {
            $v = self::calcSpacing((float)$m[1]);
            return "padding-top:{$v}rem;padding-bottom:{$v}rem";
        }
        if (preg_match('/^pt-\[(.+)\]$/', $class, $m)) {
            return "padding-top:{$m[1]}";
        }
        if (preg_match('/^pt-(\d+(?:\.\d+)?)$/', $class, $m)) {
            $v = self::calcSpacing((float)$m[1]);
            return "padding-top:{$v}rem";
        }
        if (preg_match('/^pr-\[(.+)\]$/', $class, $m)) {
            return "padding-right:{$m[1]}";
        }
        if (preg_match('/^pr-(\d+(?:\.\d+)?)$/', $class, $m)) {
            $v = self::calcSpacing((float)$m[1]);
            return "padding-right:{$v}rem";
        }
        if (preg_match('/^pb-\[(.+)\]$/', $class, $m)) {
            return "padding-bottom:{$m[1]}";
        }
        if (preg_match('/^pb-(\d+(?:\.\d+)?)$/', $class, $m)) {
            $v = self::calcSpacing((float)$m[1]);
            return "padding-bottom:{$v}rem";
        }
        if (preg_match('/^pl-\[(.+)\]$/', $class, $m)) {
            return "padding-left:{$m[1]}";
        }
        if (preg_match('/^pl-(\d+(?:\.\d+)?)$/', $class, $m)) {
            $v = self::calcSpacing((float)$m[1]);
            return "padding-left:{$v}rem";
        }

        if (preg_match('/^m-\[(.+)\]$/', $class, $m)) {
            return "margin:{$m[1]}";
        }
        if (preg_match('/^m-(\d+(?:\.\d+)?)$/', $class, $m)) {
            $v = self::calcSpacing((float)$m[1]);
            return "margin:{$v}rem";
        }
        if (preg_match('/^mx-\[(.+)\]$/', $class, $m)) {
            return "margin-left:{$m[1]};margin-right:{$m[1]}";
        }
        if (preg_match('/^mx-(\d+(?:\.\d+)?)$/', $class, $m)) {
            $v = self::calcSpacing((float)$m[1]);
            return "margin-left:{$v}rem;margin-right:{$v}rem";
        }
        if (preg_match('/^my-\[(.+)\]$/', $class, $m)) {
            return "margin-top:{$m[1]};margin-bottom:{$m[1]}";
        }
        if (preg_match('/^my-(\d+(?:\.\d+)?)$/', $class, $m)) {
            $v = self::calcSpacing((float)$m[1]);
            return "margin-top:{$v}rem;margin-bottom:{$v}rem";
        }
        if (preg_match('/^mt-\[(.+)\]$/', $class, $m)) {
            return "margin-top:{$m[1]}";
        }
        if (preg_match('/^mt-(\d+(?:\.\d+)?)$/', $class, $m)) {
            $v = self::calcSpacing((float)$m[1]);
            return "margin-top:{$v}rem";
        }
        if (preg_match('/^mr-\[(.+)\]$/', $class, $m)) {
            return "margin-right:{$m[1]}";
        }
        if (preg_match('/^mr-(\d+(?:\.\d+)?)$/', $class, $m)) {
            $v = self::calcSpacing((float)$m[1]);
            return "margin-right:{$v}rem";
        }
        if (preg_match('/^mb-\[(.+)\]$/', $class, $m)) {
            return "margin-bottom:{$m[1]}";
        }
        if (preg_match('/^mb-(\d+(?:\.\d+)?)$/', $class, $m)) {
            $v = self::calcSpacing((float)$m[1]);
            return "margin-bottom:{$v}rem";
        }
        if (preg_match('/^ml-\[(.+)\]$/', $class, $m)) {
            return "margin-left:{$m[1]}";
        }
        if (preg_match('/^ml-(\d+(?:\.\d+)?)$/', $class, $m)) {
            $v = self::calcSpacing((float)$m[1]);
            return "margin-left:{$v}rem";
        }

        if ($class === 'mx-auto') return 'margin-left:auto;margin-right:auto';
        if ($class === 'my-auto') return 'margin-top:auto;margin-bottom:auto';
        if ($class === 'm-auto') return 'margin:auto';
        if ($class === 'ml-auto') return 'margin-left:auto';
        if ($class === 'mr-auto') return 'margin-right:auto';
        if ($class === 'mt-auto') return 'margin-top:auto';
        if ($class === 'mb-auto') return 'margin-bottom:auto';

        if (preg_match('/^gap-\[(.+)\]$/', $class, $m)) {
            return "gap:{$m[1]}";
        }
        if (preg_match('/^gap-(\d+(?:\.\d+)?)$/', $class, $m)) {
            $v = self::calcSpacing((float)$m[1]);
            return "gap:{$v}rem";
        }
        if (preg_match('/^gap-x-\[(.+)\]$/', $class, $m)) {
            return "column-gap:{$m[1]}";
        }
        if (preg_match('/^gap-x-(\d+(?:\.\d+)?)$/', $class, $m)) {
            $v = self::calcSpacing((float)$m[1]);
            return "column-gap:{$v}rem";
        }
        if (preg_match('/^gap-y-\[(.+)\]$/', $class, $m)) {
            return "row-gap:{$m[1]}";
        }
        if (preg_match('/^gap-y-(\d+(?:\.\d+)?)$/', $class, $m)) {
            $v = self::calcSpacing((float)$m[1]);
            return "row-gap:{$v}rem";
        }

        if (preg_match('/^-space-y-(\d+(?:\.\d+)?)$/', $class, $m)) {
            $v = (float)$m[1] * 0.25;
            return "> *{--tw-space-y-reverse:0;margin-bottom:calc({$v}rem * calc(1 - var(--tw-space-y-reverse)));margin-top:calc({$v}rem * var(--tw-space-y-reverse))}";
        }
        if (preg_match('/^space-y-(\d+(?:\.\d+)?)$/', $class, $m)) {
            $v = (float)$m[1] * 0.25;
            return "> *{--tw-space-y-reverse:0;margin-top:calc({$v}rem * calc(1 - var(--tw-space-y-reverse)));margin-bottom:calc({$v}rem * var(--tw-space-y-reverse))}";
        }

        if (preg_match('/^-space-x-(\d+(?:\.\d+)?)$/', $class, $m)) {
            $v = (float)$m[1] * 0.25;
            return "> *{--tw-space-x-reverse:0;margin-right:calc({$v}rem * calc(1 - var(--tw-space-x-reverse)));margin-left:calc({$v}rem * var(--tw-space-x-reverse))}";
        }
        if (preg_match('/^space-x-(\d+(?:\.\d+)?)$/', $class, $m)) {
            $v = (float)$m[1] * 0.25;
            return "> *{--tw-space-x-reverse:0;margin-left:calc({$v}rem * calc(1 - var(--tw-space-x-reverse)));margin-right:calc({$v}rem * var(--tw-space-x-reverse))}";
        }

        if ($class === 'space-y') return "> *{--tw-space-y-reverse:0;margin-top:calc(0.25rem * calc(1 - var(--tw-space-y-reverse)));margin-bottom:calc(0.25rem * var(--tw-space-y-reverse))}";
        if ($class === 'space-x') return "> *{--tw-space-x-reverse:0;margin-left:calc(0.25rem * calc(1 - var(--tw-space-x-reverse)));margin-right:calc(0.25rem * var(--tw-space-x-reverse))}";
        if ($class === 'space-y-reverse') return '> *{--tw-space-y-reverse:1}';
        if ($class === 'space-x-reverse') return '> *{--tw-space-x-reverse:1}';

        return null;
    }

    public static function calcSpacing(float $value): string
    {
        $spacing = [
            '0' => '0',
            '0.5' => '0.125',
            '1' => '0.25',
            '1.5' => '0.375',
            '2' => '0.5',
            '2.5' => '0.625',
            '3' => '0.75',
            '3.5' => '0.875',
            '4' => '1',
            '5' => '1.25',
            '6' => '1.5',
            '7' => '1.75',
            '8' => '2',
            '9' => '2.25',
            '10' => '2.5',
            '11' => '2.75',
            '12' => '3',
            '14' => '3.5',
            '16' => '4',
            '20' => '5',
            '24' => '6',
            '28' => '7',
            '32' => '8',
            '36' => '9',
            '40' => '10',
            '44' => '11',
            '48' => '12',
            '52' => '13',
            '56' => '14',
            '60' => '15',
            '64' => '16',
            '72' => '18',
            '80' => '20',
            '96' => '24',
        ];
        $key = rtrim(rtrim((string)$value, '0'), '.');
        if (isset($spacing[$key])) {
            return $spacing[$key];
        }
        return (string)($value * 0.25);
    }
}
