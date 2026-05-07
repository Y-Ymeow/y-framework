<?php

declare(strict_types=1);

namespace Admin\PageBuilder\StylePanel\StyleGroups;

use Admin\PageBuilder\StylePanel\StyleGroupInterface;
use Framework\UX\Form\FormBuilder;
use Framework\UX\Form\Components\Select;
use Framework\UX\Form\Layout\Grid;

class BorderGroup implements StyleGroupInterface
{
    public function name(): string { return 'border'; }
    public function label(): string { return '边框'; }
    public function icon(): string { return 'square'; }

    public function canHandle(string $baseClass): bool
    {
        if (preg_match('/^border(-[trbl])?(-\d+)?$/', $baseClass)) return true;
        if ($baseClass === 'border-0' || preg_match('/^border-[trbl]-0$/', $baseClass)) return true;
        if (preg_match('/^rounded(-[tblr])?(-(none|sm|md|lg|xl|2xl|3xl|full|\[.*\]))?$/', $baseClass)) return true;
        if (preg_match('/^border-(solid|dashed|dotted|double|hidden|none)$/', $baseClass)) return true;
        return false;
    }

    public function fields(FormBuilder $form, array $currentClasses): void
    {
        $borderWidth = $this->findMatch($currentClasses, ['border', 'border-0', 'border-2', 'border-4', 'border-8'], '');
        $borderStyle = $this->findMatch($currentClasses, ['border-solid', 'border-dashed', 'border-dotted', 'border-double', 'border-hidden', 'border-none'], '');
        $radius = $this->findMatch($currentClasses, ['rounded-none', 'rounded-sm', 'rounded', 'rounded-md', 'rounded-lg', 'rounded-xl', 'rounded-2xl', 'rounded-3xl', 'rounded-full'], '');
        $borderColor = $this->extractBorderColor($currentClasses);

        $form->schema([
            Grid::make(3)->schema([
                Select::make('style_border_width')
                    ->label('边框宽度')
                    ->options(['' => '默认', 'border' => '1px', 'border-0' => '0', 'border-2' => '2px', 'border-4' => '4px', 'border-8' => '8px'])
                    ->default($borderWidth),
                Select::make('style_border_style')
                    ->label('边框样式')
                    ->options(['' => '默认', 'border-solid' => '实线', 'border-dashed' => '虚线', 'border-dotted' => '点线', 'border-double' => '双线', 'border-none' => '无'])
                    ->default($borderStyle),
                Select::make('style_rounded')
                    ->label('圆角')
                    ->options(['' => '默认', 'rounded-none' => '无', 'rounded-sm' => 'SM', 'rounded' => 'Default', 'rounded-md' => 'MD', 'rounded-lg' => 'LG', 'rounded-xl' => 'XL', 'rounded-2xl' => '2XL', 'rounded-3xl' => '3XL', 'rounded-full' => '圆形'])
                    ->default($radius),
            ]),
        ]);
    }

    public function extractClasses(array $data): array
    {
        $classes = [];
        $keys = ['style_border_width', 'style_border_style', 'style_rounded'];
        foreach ($keys as $key) {
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

    private function extractBorderColor(array $classes): string
    {
        foreach ($classes as $cls) {
            if (str_starts_with($cls, 'border-') && !in_array($cls, ['border', 'border-0', 'border-2', 'border-4', 'border-8', 'border-solid', 'border-dashed', 'border-dotted', 'border-double', 'border-hidden', 'border-none'])) {
                return substr($cls, 7);
            }
        }
        return '';
    }
}
