<?php

declare(strict_types=1);

namespace Framework\CSS;

class LayoutRules
{
    public static function parse(string $class, ?string $pseudo = null, ?string $originalClass = null): ?string
    {
        if ($class === 'block') return 'display:block';
        if ($class === 'inline-block') return 'display:inline-block';
        if ($class === 'inline') return 'display:inline';
        if ($class === 'flex') return 'display:flex';
        if ($class === 'inline-flex') return 'display:inline-flex';
        if ($class === 'grid') return 'display:grid';
        if ($class === 'hidden') return 'display:none';
        if ($class === 'contents') return 'display:contents';
        if ($class === 'list-item') return 'display:list-item';
        if ($class === 'flow-root') return 'display:flow-root';

        if ($class === 'flex-row') return 'flex-direction:row';
        if ($class === 'flex-row-reverse') return 'flex-direction:row-reverse';
        if ($class === 'flex-col') return 'flex-direction:column';
        if ($class === 'flex-col-reverse') return 'flex-direction:column-reverse';
        if ($class === 'flex-wrap') return 'flex-wrap:wrap';
        if ($class === 'flex-wrap-reverse') return 'flex-wrap:wrap-reverse';
        if ($class === 'flex-nowrap') return 'flex-wrap:nowrap';
        if ($class === 'inbox') return 'display:flex;flex-wrap:wrap;gap:1rem';

        if ($class === 'items-start') return 'align-items:flex-start';
        if ($class === 'items-end') return 'align-items:flex-end';
        if ($class === 'items-center') return 'align-items:center';
        if ($class === 'items-baseline') return 'align-items:baseline';
        if ($class === 'items-stretch') return 'align-items:stretch';

        if ($class === 'self-start') return 'align-self:flex-start';
        if ($class === 'self-end') return 'align-self:flex-end';
        if ($class === 'self-center') return 'align-self:center';
        if ($class === 'self-stretch') return 'align-self:stretch';

        if ($class === 'justify-start') return 'justify-content:flex-start';
        if ($class === 'justify-end') return 'justify-content:flex-end';
        if ($class === 'justify-center') return 'justify-content:center';
        if ($class === 'justify-between') return 'justify-content:space-between';
        if ($class === 'justify-around') return 'justify-content:space-around';
        if ($class === 'justify-evenly') return 'justify-content:space-evenly';

        if ($class === 'flex-1') return 'flex:1 1 0%';
        if ($class === 'flex-auto') return 'flex:1 1 auto';
        if ($class === 'flex-initial') return 'flex:0 1 auto';
        if ($class === 'flex-none') return 'flex:none';
        if ($class === 'flex-grow') return 'flex-grow:1';
        if ($class === 'flex-shrink') return 'flex-shrink:1';
        if ($class === 'flex-shrink-0') return 'flex-shrink:0';

        if (preg_match('/^w-(\d+)$/', $class, $m)) {
            $v = (int)$m[1] * 0.25;
            return "width:{$v}rem";
        }
        if (preg_match('/^w-\[(.+)\]$/', $class, $m)) {
            $m[1] = str_replace([
                '-',
                '+',
                '*',
                '/'
            ], [' - ', ' + ', ' * ', ' / '], $m[1]);
            return "width: {$m[1]}";
        }
        if ($class === 'w-full') return 'width:100%';
        if ($class === 'w-auto') return 'width:auto';
        if ($class === 'w-screen') return 'width:100vw';
        if ($class === 'w-min') return 'width:min-content';
        if ($class === 'w-max') return 'width:max-content';

        if (preg_match('/^h-(\d+)$/', $class, $m)) {
            $v = (int)$m[1] * 0.25;
            return "height:{$v}rem";
        }
        if (preg_match('/^h-\[(.+)\]$/', $class, $m)) {
            $m[1] = str_replace([
                '-',
                '+',
                '*',
                '/'
            ], [' - ', ' + ', ' * ', ' / '], $m[1]);
            return "height: {$m[1]}";
        }
        if ($class === 'h-full') return 'height:100%';
        if ($class === 'h-screen') return 'height:100vh';
        if ($class === 'h-auto') return 'height:auto';

        if ($class === 'min-h-0') return 'min-height:0';
        if ($class === 'min-h-full') return 'min-height:100%';
        if ($class === 'min-h-screen') return 'min-height:100vh';
        if (preg_match('/^min-h-\[(.+)\]$/', $class, $m)) {
            $m[1] = str_replace([
                '-',
                '+',
                '*',
                '/'
            ], [' - ', ' + ', ' * ', ' / '], $m[1]);
            return "min-height: {$m[1]}";
        }

        if ($class === 'min-w-0') return 'min-width:0';
        if ($class === 'min-w-full') return 'min-width:100%';
        if ($class === 'min-w-min') return 'min-width:min-content';
        if ($class === 'min-w-max') return 'min-width:max-content';
        if (preg_match('/^min-w-\[(.+)\]$/', $class, $m)) {
            $m[1] = str_replace([
                '-',
                '+',
                '*',
                '/'
            ], [' - ', ' + ', ' * ', ' / '], $m[1]);
            return "min-width: {$m[1]}";
        }

        if ($class === 'shrink-0') return 'flex-shrink:0';
        if ($class === 'shrink') return 'flex-shrink:1';

        if ($class === 'grow-0') return 'flex-grow:0';
        if ($class === 'grow') return 'flex-grow:1';

        if (preg_match('/^max-h-(\d+)$/', $class, $m)) {
            $v = (int)$m[1] * 0.25;
            return "max-height:{$v}rem";
        }
        if ($class === 'max-h-full') return 'max-height:100%';
        if ($class === 'max-h-screen') return 'max-height:100vh';
        if ($class === 'max-h-none') return 'max-height:none';

        if (preg_match('/^max-w-(\w+)$/', $class, $m)) {
            $maxWidths = [
                'none' => 'none',
                'xs' => '20rem',
                'sm' => '24rem',
                'md' => '28rem',
                'lg' => '32rem',
                'xl' => '36rem',
                '2xl' => '42rem',
                '3xl' => '48rem',
                '4xl' => '56rem',
                '5xl' => '64rem',
                '6xl' => '72rem',
                '7xl' => '80rem',
                'full' => '100%',
                'prose' => '65ch',
            ];
            if (isset($maxWidths[$m[1]])) {
                return "max-width:" . $maxWidths[$m[1]];
            }
        }

        if ($class === 'static') return 'position:static';
        if ($class === 'fixed') return 'position:fixed';
        if ($class === 'absolute') return 'position:absolute';
        if ($class === 'relative') return 'position:relative';
        if ($class === 'sticky') return 'position:sticky';

        if ($class === 'top') return 'top:0';
        if ($class === 'right') return 'right:0';
        if ($class === 'bottom') return 'bottom:0';
        if ($class === 'left') return 'left:0';

        if (preg_match('/^top-(\d+(?:\.\d+)?)$/', $class, $m)) {
            $v = (float)$m[1] * 0.25;
            return "top:{$v}rem";
        }
        if ($class === 'top-full') return 'top:100%';
        if ($class === 'top-0') return 'top:0';
        if (preg_match('/^bottom-(\d+(?:\.\d+)?)$/', $class, $m)) {
            $v = (float)$m[1] * 0.25;
            return "bottom:{$v}rem";
        }
        if ($class === 'bottom-full') return 'bottom:100%';
        if (preg_match('/^left-(\d+(?:\.\d+)?)$/', $class, $m)) {
            $v = (float)$m[1] * 0.25;
            return "left:{$v}rem";
        }
        if (preg_match('/^right-(\d+(?:\.\d+)?)$/', $class, $m)) {
            $v = (float)$m[1] * 0.25;
            return "right:{$v}rem";
        }
        if ($class === 'right-full') return 'right:100%';

        if ($class === 'inset-0') return 'top:0;right:0;bottom:0;left:0';
        if ($class === 'inset-auto') return 'top:auto;right:auto;bottom:auto;left:auto';

        if (preg_match('/^z-(\d+)$/', $class, $m)) {
            return "z-index:" . (int)$m[1];
        }
        if (preg_match('/^z-\[(-?\d+)\]$/', $class, $m)) {
            return "z-index:" . (int)$m[1];
        }
        if ($class === 'z-auto') return 'z-index:auto';

        if (preg_match('/^grid-cols-(\d+)$/', $class, $m)) {
            return "grid-template-columns:repeat(" . (int)$m[1] . ",minmax(0,1fr))";
        }
        if ($class === 'grid-cols-none') return 'grid-template-columns:none';

        if (preg_match('/^col-span-(\d+)$/', $class, $m)) {
            return "grid-column:span " . (int)$m[1] . "/span " . (int)$m[1];
        }
        if ($class === 'col-span-full') return 'grid-column:1/-1';
        if ($class === 'col-auto') return 'grid-column:auto';

        if ($class === 'overflow-auto') return 'overflow:auto';
        if ($class === 'overflow-hidden') return 'overflow:hidden';
        if ($class === 'overflow-visible') return 'overflow:visible';
        if ($class === 'overflow-scroll') return 'overflow:scroll';
        if ($class === 'overflow-x-auto') return 'overflow-x:auto';
        if ($class === 'overflow-x-hidden') return 'overflow-x:hidden';
        if ($class === 'overflow-y-auto') return 'overflow-y:auto';
        if ($class === 'overflow-y-hidden') return 'overflow-y:hidden';

        return null;
    }
}
