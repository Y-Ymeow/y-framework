<?php

declare(strict_types=1);

namespace Admin\PageBuilder\StylePanel\StyleGroups;

use Admin\PageBuilder\StylePanel\StyleGroupInterface;
use Framework\UX\Form\FormBuilder;
use Framework\UX\Form\Components\Select;
use Framework\UX\Form\Layout\Grid;

class LayoutGroup implements StyleGroupInterface
{
    public function name(): string { return 'layout'; }
    public function label(): string { return '布局'; }
    public function icon(): string { return 'layout-three-columns'; }

    public function canHandle(string $baseClass): bool
    {
        $layoutClasses = [
            'block', 'inline-block', 'inline', 'flex', 'inline-flex', 'grid', 'hidden',
            'flex-row', 'flex-row-reverse', 'flex-col', 'flex-col-reverse',
            'flex-wrap', 'flex-wrap-reverse', 'flex-nowrap',
            'items-start', 'items-end', 'items-center', 'items-baseline', 'items-stretch',
            'self-start', 'self-end', 'self-center', 'self-stretch',
            'justify-start', 'justify-end', 'justify-center', 'justify-between', 'justify-around', 'justify-evenly',
            'flex-1', 'flex-auto', 'flex-initial', 'flex-none', 'flex-grow', 'flex-shrink', 'flex-shrink-0',
            'static', 'fixed', 'absolute', 'relative', 'sticky',
            'overflow-auto', 'overflow-hidden', 'overflow-visible', 'overflow-scroll',
        ];

        if (in_array($baseClass, $layoutClasses)) return true;

        if (preg_match('/^(w|h|min-w|min-h|max-w|max-h)-(full|auto|screen|\[.*\]|\d+\/\d+|\d+)$/', $baseClass)) return true;
        if (preg_match('/^(top|right|bottom|left|inset)-(0|auto|full|\[.*\])$/', $baseClass)) return true;
        if (preg_match('/^z-(\d+|auto|\[.*\])$/', $baseClass)) return true;
        if (preg_match('/^grid-cols-(\d+|none)$/', $baseClass)) return true;
        if (preg_match('/^col-span-(\d+|full)$/', $baseClass)) return true;
        if (preg_match('/^(shrink|grow)(-\d+)?$/', $baseClass)) return true;

        return false;
    }

    public function fields(FormBuilder $form, array $currentClasses): void
    {
        $display = $this->findMatch($currentClasses, ['block', 'inline-block', 'inline', 'flex', 'inline-flex', 'grid', 'hidden'], '');
        $direction = $this->findMatch($currentClasses, ['flex-row', 'flex-row-reverse', 'flex-col', 'flex-col-reverse'], '');
        $align = $this->findMatch($currentClasses, ['items-start', 'items-end', 'items-center', 'items-baseline', 'items-stretch'], '');
        $justify = $this->findMatch($currentClasses, ['justify-start', 'justify-end', 'justify-center', 'justify-between', 'justify-around', 'justify-evenly'], '');
        $wrap = $this->findMatch($currentClasses, ['flex-wrap', 'flex-wrap-reverse', 'flex-nowrap'], '');
        $width = $this->findPrefixed($currentClasses, 'w-');
        $position = $this->findMatch($currentClasses, ['static', 'fixed', 'absolute', 'relative', 'sticky'], '');

        $form->schema([
            Grid::make(3)->schema([
                Select::make('style_display')
                    ->label('显示')
                    ->options(['' => '默认', 'block' => 'Block', 'inline-block' => 'Inline Block', 'inline' => 'Inline', 'flex' => 'Flex', 'inline-flex' => 'Inline Flex', 'grid' => 'Grid', 'hidden' => '隐藏'])
                    ->default($display),
                Select::make('style_flex_direction')
                    ->label('方向')
                    ->options(['' => '默认', 'flex-row' => '水平', 'flex-row-reverse' => '水平反转', 'flex-col' => '垂直', 'flex-col-reverse' => '垂直反转'])
                    ->default($direction),
                Select::make('style_position')
                    ->label('定位')
                    ->options(['' => '默认', 'static' => 'Static', 'relative' => 'Relative', 'absolute' => 'Absolute', 'fixed' => 'Fixed', 'sticky' => 'Sticky'])
                    ->default($position),
            ]),
            Grid::make(3)->schema([
                Select::make('style_align_items')
                    ->label('对齐')
                    ->options(['' => '默认', 'items-start' => '起始', 'items-center' => '居中', 'items-end' => '末尾', 'items-baseline' => '基线', 'items-stretch' => '拉伸'])
                    ->default($align),
                Select::make('style_justify_content')
                    ->label('分布')
                    ->options(['' => '默认', 'justify-start' => '起始', 'justify-center' => '居中', 'justify-end' => '末尾', 'justify-between' => '两端', 'justify-around' => '环绕', 'justify-evenly' => '均分'])
                    ->default($justify),
                Select::make('style_flex_wrap')
                    ->label('换行')
                    ->options(['' => '默认', 'flex-wrap' => '换行', 'flex-wrap-reverse' => '反向换行', 'flex-nowrap' => '不换行'])
                    ->default($wrap),
            ]),
            Grid::make(2)->schema([
                Select::make('style_width')
                    ->label('宽度')
                    ->options(['' => '默认', 'w-full' => '100%', 'w-auto' => 'Auto', 'w-screen' => 'Screen', 'w-1/2' => '50%', 'w-1/3' => '33%', 'w-2/3' => '67%', 'w-1/4' => '25%', 'w-3/4' => '75%'])
                    ->default($width),
                Select::make('style_overflow')
                    ->label('溢出')
                    ->options(['' => '默认', 'overflow-auto' => 'Auto', 'overflow-hidden' => 'Hidden', 'overflow-visible' => 'Visible', 'overflow-scroll' => 'Scroll'])
                    ->default($this->findMatch($currentClasses, ['overflow-auto', 'overflow-hidden', 'overflow-visible', 'overflow-scroll'], '')),
            ]),
        ]);
    }

    public function extractClasses(array $data): array
    {
        $classes = [];
        $simple = ['style_display', 'style_flex_direction', 'style_position', 'style_align_items', 'style_justify_content', 'style_flex_wrap', 'style_width', 'style_overflow'];
        foreach ($simple as $key) {
            if (!empty($data[$key])) {
                $classes[] = $data[$key];
            }
        }
        return $classes;
    }

    private function findMatch(array $classes, array $options, string $default): string
    {
        foreach ($options as $option) {
            if (in_array($option, $classes)) return $option;
        }
        return $default;
    }

    private function findPrefixed(array $classes, string $prefix): string
    {
        foreach ($classes as $cls) {
            if (str_starts_with($cls, $prefix)) return $cls;
        }
        return '';
    }
}
