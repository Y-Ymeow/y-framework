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
        $this->registerJs('language-switcher', '
            return {
                init(el) {
                    el.querySelectorAll("[data-lang-btn]").forEach(btn => {
                        btn.addEventListener("click", () => {
                            const locale = btn.dataset.langBtn;
                            if (window.$locale) {
                                window.$locale(locale);
                            }
                        });
                    });
                }
            };
        ');
    }

    protected function toElement(): Element
    {
        $currentLocale = $this->getCurrentLocale();

        $buttons = [];
        foreach ($this->locales as $locale) {
            $isActive = $locale === $currentLocale;

            $btn = Element::make('button')
                ->attr('type', 'button')
                ->data('lang-btn', $locale)
                ->class('ux-lang-btn')
                ->class("ux-lang-btn-{$this->size}");

            if ($this->variant === 'outline') {
                $btn->class('ux-lang-btn-outline');
            }
            if ($this->pill) {
                $btn->class('ux-lang-btn-pill');
            }
            if ($isActive) {
                $btn->class('ux-lang-btn-active');
            }

            $btn->text($this->getLabel($locale));
            $buttons[] = $btn;
        }

        $wrapper = Element::make('div')
            ->class('ux-lang-switcher')
            ->class($this->pill ? 'ux-lang-switcher-pill' : '')
            ->children(...$buttons);

        return $this->buildElement($wrapper);
    }
}
