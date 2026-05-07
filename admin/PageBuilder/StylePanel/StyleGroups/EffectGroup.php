<?php

declare(strict_types=1);

namespace Admin\PageBuilder\StylePanel\StyleGroups;

use Admin\PageBuilder\StylePanel\StyleGroupInterface;
use Framework\UX\Form\FormBuilder;
use Framework\UX\Form\Components\Select;
use Framework\UX\Form\Layout\Grid;

class EffectGroup implements StyleGroupInterface
{
    public function name(): string { return 'effect'; }
    public function label(): string { return '效果'; }
    public function icon(): string { return 'stars'; }

    public function canHandle(string $baseClass): bool
    {
        if (preg_match('/^shadow(-xs|-sm|-md|-lg|-xl|-2xl|-inner|-none)?$/', $baseClass)) return true;
        if (preg_match('/^opacity-(\d+|\d+\/\d+|\[.*\])$/', $baseClass)) return true;
        if (preg_match('/^transition(-all|-colors|-opacity|-shadow|-transform|-none)?$/', $baseClass)) return true;
        if (preg_match('/^duration-\d+$/', $baseClass)) return true;
        if (in_array($baseClass, ['ease-linear', 'ease-in', 'ease-out', 'ease-in-out'])) return true;
        if (preg_match('/^animate-(spin|pulse|bounce|fade-in|fade-out|slide-up|slide-down|scale-in|ping|slide-in-right)$/', $baseClass)) return true;
        return false;
    }

    public function fields(FormBuilder $form, array $currentClasses): void
    {
        $shadow = $this->findMatch($currentClasses, ['shadow-xs', 'shadow-sm', 'shadow', 'shadow-md', 'shadow-lg', 'shadow-xl', 'shadow-2xl', 'shadow-inner', 'shadow-none'], '');
        $opacity = $this->extractOpacity($currentClasses);
        $transition = $this->findMatch($currentClasses, ['transition', 'transition-all', 'transition-colors', 'transition-opacity', 'transition-shadow', 'transition-transform', 'transition-none'], '');
        $duration = $this->extractPrefixed($currentClasses, 'duration-');
        $easing = $this->findMatch($currentClasses, ['ease-linear', 'ease-in', 'ease-out', 'ease-in-out'], '');
        $animate = $this->findMatch($currentClasses, ['animate-spin', 'animate-pulse', 'animate-bounce', 'animate-fade-in', 'animate-fade-out', 'animate-slide-up', 'animate-slide-down', 'animate-scale-in', 'animate-ping', 'animate-slide-in-right'], '');

        $form->schema([
            Grid::make(3)->schema([
                Select::make('style_shadow')
                    ->label('阴影')
                    ->options(['' => '默认', 'shadow-none' => '无', 'shadow-xs' => 'XS', 'shadow-sm' => 'SM', 'shadow' => 'Default', 'shadow-md' => 'MD', 'shadow-lg' => 'LG', 'shadow-xl' => 'XL', 'shadow-2xl' => '2XL', 'shadow-inner' => '内阴影'])
                    ->default($shadow),
                Select::make('style_opacity')
                    ->label('透明度')
                    ->options(['' => '默认', '0' => '0%', '5' => '5%', '10' => '10%', '20' => '20%', '25' => '25%', '30' => '30%', '40' => '40%', '50' => '50%', '60' => '60%', '70' => '70%', '75' => '75%', '80' => '80%', '90' => '90%', '95' => '95%', '100' => '100%'])
                    ->default($opacity),
                Select::make('style_animate')
                    ->label('动画')
                    ->options(['' => '无', 'animate-spin' => '旋转', 'animate-pulse' => '脉冲', 'animate-bounce' => '弹跳', 'animate-fade-in' => '淡入', 'animate-fade-out' => '淡出', 'animate-slide-up' => '上滑', 'animate-slide-down' => '下滑', 'animate-scale-in' => '缩放', 'animate-ping' => 'Ping'])
                    ->default($animate),
            ]),
            Grid::make(3)->schema([
                Select::make('style_transition')
                    ->label('过渡')
                    ->options(['' => '默认', 'transition' => 'Default', 'transition-all' => 'All', 'transition-colors' => '颜色', 'transition-opacity' => '透明度', 'transition-shadow' => '阴影', 'transition-transform' => '变换', 'transition-none' => '无'])
                    ->default($transition),
                Select::make('style_duration')
                    ->label('时长')
                    ->options(['' => '默认', 'duration-75' => '75ms', 'duration-100' => '100ms', 'duration-150' => '150ms', 'duration-200' => '200ms', 'duration-300' => '300ms', 'duration-500' => '500ms', 'duration-700' => '700ms', 'duration-1000' => '1000ms'])
                    ->default($duration),
                Select::make('style_easing')
                    ->label('缓动')
                    ->options(['' => '默认', 'ease-linear' => '线性', 'ease-in' => '渐入', 'ease-out' => '渐出', 'ease-in-out' => '渐入渐出'])
                    ->default($easing),
            ]),
        ]);
    }

    public function extractClasses(array $data): array
    {
        $classes = [];
        if (!empty($data['style_shadow'])) $classes[] = $data['style_shadow'];
        if (!empty($data['style_opacity'])) $classes[] = 'opacity-' . $data['style_opacity'];
        if (!empty($data['style_animate'])) $classes[] = $data['style_animate'];
        if (!empty($data['style_transition'])) $classes[] = $data['style_transition'];
        if (!empty($data['style_duration'])) $classes[] = $data['style_duration'];
        if (!empty($data['style_easing'])) $classes[] = $data['style_easing'];
        return $classes;
    }

    private function findMatch(array $classes, array $options, string $default): string
    {
        foreach ($options as $option) {
            if (in_array($option, $classes)) return $option;
        }
        return $default;
    }

    private function extractOpacity(array $classes): string
    {
        foreach ($classes as $cls) {
            if (preg_match('/^opacity-(\d+)$/', $cls, $m)) return $m[1];
        }
        return '';
    }

    private function extractPrefixed(array $classes, string $prefix): string
    {
        foreach ($classes as $cls) {
            if (str_starts_with($cls, $prefix)) return $cls;
        }
        return '';
    }
}
