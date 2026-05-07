<?php

declare(strict_types=1);

namespace Admin\PageBuilder\StylePanel\StyleGroups;

use Admin\PageBuilder\StylePanel\StyleGroupInterface;
use Framework\UX\Form\FormBuilder;
use Framework\UX\Form\Components\Select;
use Framework\UX\Form\Layout\Grid;

class TypographyGroup implements StyleGroupInterface
{
    public function name(): string { return 'typography'; }
    public function label(): string { return '排版'; }
    public function icon(): string { return 'type'; }

    public function canHandle(string $baseClass): bool
    {
        if (preg_match('/^text-(xs|sm|base|lg|xl|2xl|3xl|4xl|5xl|6xl|7xl|8xl|9xl|\[.*\])$/', $baseClass)) return true;
        if (preg_match('/^font-(thin|extralight|light|normal|medium|semibold|bold|extrabold|black)$/', $baseClass)) return true;
        if (in_array($baseClass, ['text-left', 'text-center', 'text-right', 'text-justify',
            'uppercase', 'lowercase', 'capitalize', 'normal-case',
            'underline', 'overline', 'line-through', 'no-underline',
            'italic', 'not-italic', 'truncate'])) return true;
        if (preg_match('/^leading-(none|tight|snug|normal|relaxed|loose|\d+|\[.*\])$/', $baseClass)) return true;
        if (preg_match('/^tracking-(tighter|tight|normal|wide|wider|widest|\[.*\])$/', $baseClass)) return true;
        if (preg_match('/^text-\[.*\]$/', $baseClass)) return true;
        return false;
    }

    public function fields(FormBuilder $form, array $currentClasses): void
    {
        $fontSize = $this->findPrefixed($currentClasses, 'text-', ['text-left', 'text-center', 'text-right', 'text-justify']);
        $fontWeight = $this->findPrefixedMatch($currentClasses, 'font-');
        $textAlign = $this->findMatch($currentClasses, ['text-left', 'text-center', 'text-right', 'text-justify'], '');
        $leading = $this->findPrefixedMatch($currentClasses, 'leading-');
        $tracking = $this->findPrefixedMatch($currentClasses, 'tracking-');
        $transform = $this->findMatch($currentClasses, ['uppercase', 'lowercase', 'capitalize', 'normal-case'], '');
        $decoration = $this->findMatch($currentClasses, ['underline', 'overline', 'line-through', 'no-underline'], '');

        $form->schema([
            Grid::make(3)->schema([
                Select::make('style_font_size')
                    ->label('字号')
                    ->options(['' => '默认', 'text-xs' => 'XS', 'text-sm' => 'SM', 'text-base' => 'Base', 'text-lg' => 'LG', 'text-xl' => 'XL', 'text-2xl' => '2XL', 'text-3xl' => '3XL', 'text-4xl' => '4XL', 'text-5xl' => '5XL', 'text-6xl' => '6XL'])
                    ->default($fontSize),
                Select::make('style_font_weight')
                    ->label('字重')
                    ->options(['' => '默认', 'font-thin' => 'Thin(100)', 'font-extralight' => 'ExtraLight(200)', 'font-light' => 'Light(300)', 'font-normal' => 'Normal(400)', 'font-medium' => 'Medium(500)', 'font-semibold' => 'Semibold(600)', 'font-bold' => 'Bold(700)', 'font-extrabold' => 'ExtraBold(800)', 'font-black' => 'Black(900)'])
                    ->default($fontWeight),
                Select::make('style_text_align')
                    ->label('对齐')
                    ->options(['' => '默认', 'text-left' => '左', 'text-center' => '居中', 'text-right' => '右', 'text-justify' => '两端'])
                    ->default($textAlign),
            ]),
            Grid::make(3)->schema([
                Select::make('style_leading')
                    ->label('行高')
                    ->options(['' => '默认', 'leading-none' => 'None', 'leading-tight' => 'Tight', 'leading-snug' => 'Snug', 'leading-normal' => 'Normal', 'leading-relaxed' => 'Relaxed', 'leading-loose' => 'Loose'])
                    ->default($leading),
                Select::make('style_tracking')
                    ->label('字距')
                    ->options(['' => '默认', 'tracking-tighter' => 'Tighter', 'tracking-tight' => 'Tight', 'tracking-normal' => 'Normal', 'tracking-wide' => 'Wide', 'tracking-wider' => 'Wider', 'tracking-widest' => 'Widest'])
                    ->default($tracking),
                Select::make('style_text_transform')
                    ->label('变换')
                    ->options(['' => '默认', 'uppercase' => '大写', 'lowercase' => '小写', 'capitalize' => '首字母', 'normal-case' => '正常'])
                    ->default($transform),
            ]),
        ]);
    }

    public function extractClasses(array $data): array
    {
        $classes = [];
        $keys = ['style_font_size', 'style_font_weight', 'style_text_align', 'style_leading', 'style_tracking', 'style_text_transform'];
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

    private function findPrefixed(array $classes, string $prefix, array $exclude): string
    {
        foreach ($classes as $cls) {
            if (str_starts_with($cls, $prefix) && !in_array($cls, $exclude)) return $cls;
        }
        return '';
    }

    private function findPrefixedMatch(array $classes, string $prefix): string
    {
        foreach ($classes as $cls) {
            if (str_starts_with($cls, $prefix)) return $cls;
        }
        return '';
    }
}
