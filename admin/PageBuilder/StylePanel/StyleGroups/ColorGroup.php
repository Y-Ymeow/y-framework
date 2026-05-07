<?php

declare(strict_types=1);

namespace Admin\PageBuilder\StylePanel\StyleGroups;

use Admin\PageBuilder\StylePanel\StyleGroupInterface;
use Framework\UX\Form\FormBuilder;
use Framework\UX\Form\Components\Select;
use Framework\UX\Form\Layout\Grid;

class ColorGroup implements StyleGroupInterface
{
    private array $colors = [
        '' => '默认', 'transparent' => '透明', 'black' => '黑', 'white' => '白',
        'gray-50' => 'Gray 50', 'gray-100' => 'Gray 100', 'gray-200' => 'Gray 200', 'gray-300' => 'Gray 300',
        'gray-400' => 'Gray 400', 'gray-500' => 'Gray 500', 'gray-600' => 'Gray 600', 'gray-700' => 'Gray 700',
        'gray-800' => 'Gray 800', 'gray-900' => 'Gray 900',
        'red-50' => 'Red 50', 'red-100' => 'Red 100', 'red-200' => 'Red 200', 'red-300' => 'Red 300',
        'red-400' => 'Red 400', 'red-500' => 'Red 500', 'red-600' => 'Red 600', 'red-700' => 'Red 700',
        'red-800' => 'Red 800', 'red-900' => 'Red 900',
        'blue-50' => 'Blue 50', 'blue-100' => 'Blue 100', 'blue-200' => 'Blue 200', 'blue-300' => 'Blue 300',
        'blue-400' => 'Blue 400', 'blue-500' => 'Blue 500', 'blue-600' => 'Blue 600', 'blue-700' => 'Blue 700',
        'blue-800' => 'Blue 800', 'blue-900' => 'Blue 900',
        'green-50' => 'Green 50', 'green-100' => 'Green 100', 'green-200' => 'Green 200', 'green-300' => 'Green 300',
        'green-400' => 'Green 400', 'green-500' => 'Green 500', 'green-600' => 'Green 600', 'green-700' => 'Green 700',
        'green-800' => 'Green 800', 'green-900' => 'Green 900',
        'yellow-50' => 'Yellow 50', 'yellow-100' => 'Yellow 100', 'yellow-200' => 'Yellow 200', 'yellow-300' => 'Yellow 300',
        'yellow-400' => 'Yellow 400', 'yellow-500' => 'Yellow 500', 'yellow-600' => 'Yellow 600',
        'purple-50' => 'Purple 50', 'purple-100' => 'Purple 100', 'purple-200' => 'Purple 200', 'purple-300' => 'Purple 300',
        'purple-400' => 'Purple 400', 'purple-500' => 'Purple 500', 'purple-600' => 'Purple 600', 'purple-700' => 'Purple 700',
        'purple-800' => 'Purple 800', 'purple-900' => 'Purple 900',
        'indigo-50' => 'Indigo 50', 'indigo-100' => 'Indigo 100', 'indigo-200' => 'Indigo 200', 'indigo-300' => 'Indigo 300',
        'indigo-400' => 'Indigo 400', 'indigo-500' => 'Indigo 500', 'indigo-600' => 'Indigo 600', 'indigo-700' => 'Indigo 700',
        'indigo-800' => 'Indigo 800', 'indigo-900' => 'Indigo 900',
        'pink-50' => 'Pink 50', 'pink-100' => 'Pink 100', 'pink-200' => 'Pink 200', 'pink-300' => 'Pink 300',
        'pink-400' => 'Pink 400', 'pink-500' => 'Pink 500', 'pink-600' => 'Pink 600',
        'orange-50' => 'Orange 50', 'orange-100' => 'Orange 100', 'orange-200' => 'Orange 200', 'orange-300' => 'Orange 300',
        'orange-400' => 'Orange 400', 'orange-500' => 'Orange 500', 'orange-600' => 'Orange 600',
    ];

    public function name(): string { return 'color'; }
    public function label(): string { return '颜色'; }
    public function icon(): string { return 'palette'; }

    public function canHandle(string $baseClass): bool
    {
        if (preg_match('/^(text|bg|border)-(transparent|black|white|\[.*\])$/', $baseClass)) return true;
        if (preg_match('/^(text|bg|border)-(gray|red|green|blue|yellow|orange|purple|indigo|pink|rose|cyan|emerald|teal|slate|amber)-(\d+)$/', $baseClass)) return true;
        if (preg_match('/^bg-gradient-to-[rltb]{1,2}$/', $baseClass)) return true;
        if (preg_match('/^(from|via|to)-(transparent|black|white|\[.*\])$/', $baseClass)) return true;
        if (preg_match('/^(from|via|to)-(gray|red|green|blue|yellow|orange|purple|indigo|pink|rose|cyan|emerald|teal|slate|amber)-(\d+)$/', $baseClass)) return true;
        return false;
    }

    public function fields(FormBuilder $form, array $currentClasses): void
    {
        $textColor = $this->extractColorClass($currentClasses, 'text-');
        $bgColor = $this->extractColorClass($currentClasses, 'bg-');
        $gradient = $this->findMatch($currentClasses, ['bg-gradient-to-r', 'bg-gradient-to-l', 'bg-gradient-to-t', 'bg-gradient-to-b', 'bg-gradient-to-tr', 'bg-gradient-to-tl', 'bg-gradient-to-br', 'bg-gradient-to-bl'], '');
        $fromColor = $this->extractColorClass($currentClasses, 'from-');
        $toColor = $this->extractColorClass($currentClasses, 'to-');

        $form->schema([
            Grid::make(2)->schema([
                Select::make('style_text_color')
                    ->label('文字颜色')
                    ->options($this->colors)
                    ->default($textColor),
                Select::make('style_bg_color')
                    ->label('背景颜色')
                    ->options($this->colors)
                    ->default($bgColor),
            ]),
            Grid::make(3)->schema([
                Select::make('style_gradient')
                    ->label('渐变方向')
                    ->options(['' => '无', 'bg-gradient-to-r' => '→', 'bg-gradient-to-l' => '←', 'bg-gradient-to-t' => '↑', 'bg-gradient-to-b' => '↓', 'bg-gradient-to-tr' => '↗', 'bg-gradient-to-br' => '↘'])
                    ->default($gradient),
                Select::make('style_from_color')
                    ->label('渐变起始')
                    ->options($this->colors)
                    ->default($fromColor),
                Select::make('style_to_color')
                    ->label('渐变结束')
                    ->options($this->colors)
                    ->default($toColor),
            ]),
        ]);
    }

    public function extractClasses(array $data): array
    {
        $classes = [];
        if (!empty($data['style_text_color'])) $classes[] = 'text-' . $data['style_text_color'];
        if (!empty($data['style_bg_color'])) $classes[] = 'bg-' . $data['style_bg_color'];
        if (!empty($data['style_gradient'])) $classes[] = $data['style_gradient'];
        if (!empty($data['style_from_color'])) $classes[] = 'from-' . $data['style_from_color'];
        if (!empty($data['style_to_color'])) $classes[] = 'to-' . $data['style_to_color'];
        return $classes;
    }

    private function extractColorClass(array $classes, string $prefix): string
    {
        $colorPrefixes = ['gray', 'red', 'green', 'blue', 'yellow', 'orange', 'purple', 'indigo', 'pink', 'rose', 'cyan', 'emerald', 'teal', 'slate', 'amber'];
        foreach ($classes as $cls) {
            if (!str_starts_with($cls, $prefix)) continue;
            $colorPart = substr($cls, strlen($prefix));
            if (in_array($colorPart, ['transparent', 'black', 'white'])) return $colorPart;
            foreach ($colorPrefixes as $cp) {
                if (preg_match('/^' . $cp . '-(\d+)$/', $colorPart, $m)) return $colorPart;
            }
        }
        return '';
    }

    private function findMatch(array $classes, array $options, string $default): string
    {
        foreach ($options as $option) {
            if (in_array($option, $classes)) return $option;
        }
        return $default;
    }
}
