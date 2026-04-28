<?php

declare(strict_types=1);

namespace Framework\CSS;

class AnimationRules
{
    private static array $animations = [
        'animate-spin' => [
            'name' => 'spin',
            'keyframes' => '@keyframes spin{to{transform:rotate(360deg)}}',
            'css' => 'animation:spin 1s linear infinite',
        ],
        'animate-pulse' => [
            'name' => 'pulse',
            'keyframes' => '@keyframes pulse{0%,100%{opacity:1}50%{opacity:.5}}',
            'css' => 'animation:pulse 2s cubic-bezier(0.4,0,0.6,1) infinite',
        ],
        'animate-bounce' => [
            'name' => 'bounce',
            'keyframes' => '@keyframes bounce{0%,100%{transform:translateY(-25%);animation-timing-function:cubic-bezier(0.8,0,1,1)}50%{transform:none;animation-timing-function:cubic-bezier(0,0,0.2,1)}}',
            'css' => 'animation:bounce 1s infinite',
        ],
        'animate-fade-in' => [
            'name' => 'fadeIn',
            'keyframes' => '@keyframes fadeIn{from{opacity:0}to{opacity:1}}',
            'css' => 'animation:fadeIn 0.3s ease-out',
        ],
        'animate-fade-out' => [
            'name' => 'fadeOut',
            'keyframes' => '@keyframes fadeOut{from{opacity:1}to{opacity:0}}',
            'css' => 'animation:fadeOut 0.3s ease-out',
        ],
        'animate-slide-up' => [
            'name' => 'slideUp',
            'keyframes' => '@keyframes slideUp{from{transform:translateY(100%)}to{transform:translateY(0)}}',
            'css' => 'animation:slideUp 0.3s ease-out',
        ],
        'animate-slide-down' => [
            'name' => 'slideDown',
            'keyframes' => '@keyframes slideDown{from{transform:translateY(0)}to{transform:translateY(100%)}}',
            'css' => 'animation:slideDown 0.3s ease-out',
        ],
        'animate-scale-in' => [
            'name' => 'scaleIn',
            'keyframes' => '@keyframes scaleIn{from{transform:scale(0.95);opacity:0}to{transform:scale(1);opacity:1}}',
            'css' => 'animation:scaleIn 0.2s ease-out',
        ],
        'animate-ping' => [
            'name' => 'ping',
            'keyframes' => '@keyframes ping{75%,100%{transform:scale(2);opacity:0}}',
            'css' => 'animation:ping 1s cubic-bezier(0,0,0.2,1) infinite',
        ],
        'animate-slide-in-right' => [
            'name' => 'slideInRight',
            'keyframes' => '@keyframes slideInRight{from{transform:translateX(100%)}to{transform:translateX(0)}}',
            'css' => 'animation:slideInRight 0.3s ease-out',
        ],
    ];

    public static function parse(string $class, ?string $pseudo = null, ?string $originalClass = null): ?string
    {
        if (!isset(self::$animations[$class])) {
            return null;
        }
        return self::$animations[$class]['css'];
    }

    public static function getKeyframes(string $class): ?string
    {
        if (!isset(self::$animations[$class])) {
            return null;
        }
        return self::$animations[$class]['keyframes'];
    }

    public static function buildAllKeyframes(array $classes): string
    {
        $keyframes = [];
        foreach ($classes as $class) {
            if ($kf = self::getKeyframes($class)) {
                $keyframes[] = $kf;
            }
        }
        return implode("\n", $keyframes);
    }
}
