<?php

declare(strict_types=1);

namespace Admin\PageBuilder\Components\Sections;

use Admin\PageBuilder\Components\ComponentType;
use Framework\UX\Form\FormBuilder;
use Framework\UX\Form\Components\TextInput;
use Framework\View\Base\Element;

class CTA extends ComponentType
{
    public function name(): string
    {
        return 'cta';
    }
    public function label(): string
    {
        return '行动号召';
    }
    public function icon(): string
    {
        return 'cursor-fill';
    }
    public function category(): string
    {
        return 'sections';
    }

    public function settings(FormBuilder $form): void
    {
        $form->schema([
            TextInput::make('title')
                ->label('标题')
                ->default('准备好开始了吗？'),
            TextInput::make('subtitle')
                ->label('副标题')
                ->default('立即联系我们，获取专属解决方案'),
            TextInput::make('button_text')
                ->label('按钮文字')
                ->default('立即开始'),
            TextInput::make('button_url')
                ->label('按钮链接')
                ->default('#'),
        ]);
    }

    public function styleTargets(): array
    {
        return [
            'root' => '根容器',
            'title' => '标题',
            'subtitle' => '副标题',
            'button' => '按钮',
        ];
    }

    public function render(array $settings): Element
    {
        $title = $this->setting($settings, 'title', '准备好开始了吗？');
        $subtitle = $this->setting($settings, 'subtitle', '');
        $buttonText = $this->setting($settings, 'button_text', '立即开始');
        $buttonUrl = $this->setting($settings, 'button_url', '#');

        $cta = Element::make('section')
            ->class('text-center', 'py-12', 'px-8', 'bg-gray-50', 'border-t', 'border-gray-200')
            ->attr('data-pb-style', 'root');

        $cta->child(
            Element::make('h2')
                ->class('text-2xl', 'font-bold', 'mb-3', 'text-gray-900')
                ->attr('data-pb-style', 'title')
                ->text($title)
        );

        if ($subtitle) {
            $cta->child(
                Element::make('p')
                    ->class('text-gray-500', 'mb-6')
                    ->attr('data-pb-style', 'subtitle')
                    ->text($subtitle)
            );
        }

        if ($buttonText) {
            $cta->child(
                Element::make('a')
                    ->class('inline-flex', 'px-6', 'py-3', 'text-sm', 'font-semibold', 'bg-blue-600', 'text-white', 'rounded-md', 'no-underline', 'hover:bg-blue-700')
                    ->attr('data-pb-style', 'button')
                    ->attr('href', $buttonUrl)
                    ->text($buttonText)
            );
        }

        $this->applyStyles($cta, $settings);

        return $cta;
    }
}
