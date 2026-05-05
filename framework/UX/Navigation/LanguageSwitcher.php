<?php

declare(strict_types=1);

namespace Framework\UX\Navigation;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;
use Framework\Intl\Translator;

/**
 * 语言切换器
 *
 * 提供便捷的语言切换按钮，基于 intl.js 的 $locale 函数，无需手写 JS。
 *
 * ## 使用方式
 *
 * ```php
 * // 基础用法 - 默认支持 en/zh
 * LanguageSwitcher::make()
 *
 * // 自定义语言和标签
 * LanguageSwitcher::make()
 *     ->locales(['en', 'zh', 'ja'])
 *     ->labels(['en' => 'EN', 'zh' => '中文', 'ja' => '日本語'])
 *
 * // 自定义样式
 * LanguageSwitcher::make()->outline()->sm()
 * LanguageSwitcher::make()->pill()
 *
 * // 在 LiveComponent 中使用
 * public function render(): Element
 * {
 *     return Element::make('nav')->children(
 *         Element::make('h1')->intl('site.title'),
 *         LanguageSwitcher::make()
 *     );
 * }
 * ```
 *
 * @ux-category Navigation
 * @ux-since 1.0.0
 * @ux-example LanguageSwitcher::make()
 * @ux-example LanguageSwitcher::make()->locales(['en', 'zh'])->labels(['en' => 'EN', 'zh' => '中文'])
 */
class LanguageSwitcher extends UXComponent
{
    protected array $locales = ['en', 'zh'];

    protected array $labels = [];

    protected string $variant = 'solid';

    protected string $size = 'md';

    protected bool $pill = false;

    /**
     * 设置支持的语言列表
     */
    public function locales(array $locales): static
    {
        $this->locales = $locales;
        return $this;
    }

    /**
     * 设置语言显示标签
     */
    public function labels(array $labels): static
    {
        $this->labels = $labels;
        return $this;
    }

    /**
     * 边框样式
     */
    public function outline(bool $outline = true): static
    {
        $this->variant = $outline ? 'outline' : 'solid';
        return $this;
    }

    /**
     * 胶囊样式
     */
    public function pill(bool $pill = true): static
    {
        $this->pill = $pill;
        return $this;
    }

    /**
     * 小尺寸
     */
    public function sm(): static
    {
        $this->size = 'sm';
        return $this;
    }

    /**
     * 大尺寸
     */
    public function lg(): static
    {
        $this->size = 'lg';
        return $this;
    }

    /**
     * 获取语言的显示标签
     */
    private function getLabel(string $locale): string
    {
        if (!empty($this->labels[$locale])) {
            return $this->labels[$locale];
        }

        $defaultLabels = [
            'en' => 'EN',
            'zh' => '中文',
        ];

        return $defaultLabels[$locale] ?? strtoupper($locale);
    }

    /**
     * 获取当前语言
     */
    private function getCurrentLocale(): string
    {
        return Translator::getLocale();
    }

    protected function init(): void
    {
        $this->registerCss(<<<'CSS'
.ux-lang-switcher {
    display: inline-flex;
    align-items: center;
    gap: 0;
    border-radius: 0.375rem;
    overflow: hidden;
    border: 1px solid #d1d5db;
}
.ux-lang-switcher-pill {
    border-radius: 9999px;
}
.ux-lang-btn {
    padding: 0.375rem 0.75rem;
    font-size: 0.8125rem;
    font-weight: 500;
    color: #6b7280;
    background: #fff;
    border: none;
    cursor: pointer;
    transition: background-color 0.15s, color 0.15s;
    white-space: nowrap;
}
.ux-lang-btn:not(:last-child) {
    border-right: 1px solid #d1d5db;
}
.ux-lang-btn:hover {
    background: #f9fafb;
    color: #374151;
}
.ux-lang-btn-active {
    background: #3b82f6;
    color: #fff;
}
.ux-lang-btn-active:hover {
    background: #2563eb;
    color: #fff;
}
.ux-lang-btn-outline {
    background: transparent;
    border: 1px solid #d1d5db;
}
.ux-lang-btn-outline:not(:last-child) {
    border-right: 1px solid #d1d5db;
}
.ux-lang-btn-outline.ux-lang-btn-active {
    background: #3b82f6;
    border-color: #3b82f6;
    color: #fff;
}
.ux-lang-btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}
.ux-lang-btn-lg {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}
.ux-lang-btn-pill {
    border-radius: 9999px;
}
CSS
        );
    }

    protected function toElement(): Element
    {
        $currentLocale = $this->getCurrentLocale();

        $buttons = [];
        foreach ($this->locales as $locale) {
            $isActive = $locale === $currentLocale;

            $btn = Element::make('button')
                ->attr('type', 'button')
                ->attr('data-on:click', "locale = '{$locale}';\$locale('{$locale}')")
                ->class('ux-lang-btn')
                ->class("ux-lang-btn-{$this->size}")
                ->bindAttr('class', "{'ux-lang-btn-active': locale === '{$locale}'}");

            if ($this->variant === 'outline') {
                $btn->class('ux-lang-btn-outline');
            }
            if ($this->pill) {
                $btn->class('ux-lang-btn-pill');
            }

            $btn->text($this->getLabel($locale));
            $buttons[] = $btn;
        }

        $wrapper = Element::make('div')
            ->class('ux-lang-switcher')
            ->class($this->pill ? 'ux-lang-switcher-pill' : '')
            ->attr('data-state', '{"locale": "' . $currentLocale . '"}')
            ->children(...$buttons);

        return $this->buildElement($wrapper);
    }
}
