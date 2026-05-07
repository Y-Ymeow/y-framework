<?php

declare(strict_types=1);

namespace Admin\PageBuilder\Components\Sections;

use Admin\PageBuilder\Components\ComponentType;
use Framework\UX\Form\FormBuilder;
use Framework\UX\Form\Components\Select;
use Framework\UX\Form\Components\Textarea;
use Framework\View\Base\Element;

class FeatureGrid extends ComponentType
{
    public function name(): string { return 'feature_grid'; }
    public function label(): string { return '特性展示'; }
    public function icon(): string { return 'grid-3x3-gap-fill'; }
    public function category(): string { return 'sections'; }

    public function settings(FormBuilder $form): void
    {
        $form->schema([
            Select::make('columns')
                ->label('列数')
                ->options(['2' => '2列', '3' => '3列', '4' => '4列'])
                ->default('3'),
            Textarea::make('features')
                ->label('特性列表 (每行: 图标|标题|描述)')
                ->rows(4)
                ->default("lightning|快速|极致的性能表现\nshield|安全|企业级安全保障\ngear|灵活|高度可定制化"),
        ]);
    }

    public function render(array $settings): Element
    {
        $columns = $this->setting($settings, 'columns', '3');
        $featuresRaw = $this->setting($settings, 'features', '');
        $className = $this->setting($settings, 'className', '');

        $grid = Element::make('div')
            ->class('grid', "grid-cols-{$columns}", 'gap-8', 'py-12', 'px-8', $className);

        $features = explode("\n", $featuresRaw);
        foreach ($features as $feature) {
            $parts = explode('|', trim($feature), 3);
            if (count($parts) < 2) continue;

            $icon = trim($parts[0]);
            $title = trim($parts[1]);
            $desc = isset($parts[2]) ? trim($parts[2]) : '';

            $item = Element::make('div')->class('text-center', 'p-6');
            $item->child(
                Element::make('div')
                    ->class('text-3xl', 'mb-3', 'text-blue-600')
                    ->html('<i class="bi bi-' . $icon . '"></i>')
            );
            $item->child(
                Element::make('div')
                    ->class('text-lg', 'font-semibold', 'mb-2', 'text-gray-900')
                    ->text($title)
            );
            if ($desc) {
                $item->child(
                    Element::make('div')
                        ->class('text-sm', 'text-gray-500', 'leading-relaxed')
                        ->text($desc)
                );
            }
            $grid->child($item);
        }

        return $grid;
    }
}
