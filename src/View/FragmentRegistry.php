<?php

declare(strict_types=1);

namespace Framework\View;

use Framework\View\Base\Element;

class FragmentRegistry
{
    private static array $fragments = [];
    private static array $targets = []; // [name => mode]

    /**
     * 设置本次请求需要“抓取”的分片名称及其模式 (replace, append, prepend)
     */
    public static function setTargets(array $targets): void
    {
        foreach ($targets as $name => $mode) {
            if (is_int($name)) {
                self::$targets[$mode] = 'replace'; // 兼容 ['name1', 'name2'] 格式
            } else {
                self::$targets[$name] = $mode;
            }
        }
    }

    /**
     * 收集分片 HTML
     */
    public static function record(string $name, Element & $element): void
    {
        if (isset(self::$targets[$name])) {
            self::$fragments[$name] = [
                'element' => $element,
                'mode' => self::$targets[$name]
            ];
        }
    }

    public static function getFragments(): array
    {
        return self::$fragments;
    }

    public static function reset(): void
    {
        self::$fragments = [];
        self::$targets = [];
    }
}
