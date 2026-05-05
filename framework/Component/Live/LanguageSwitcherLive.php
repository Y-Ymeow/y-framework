<?php

declare(strict_types=1);

namespace Framework\Component\Live;

use Framework\Component\Live\Attribute\LiveAction;
use Framework\Component\Live\Attribute\Persistent;
use Framework\View\Base\Element;
use Framework\Intl\Translator;

/**
 * 语言切换器 Live 组件
 *
 * 使用 #[Persistent('local')] 实现语言偏好持久化。
 * 用户选择语言后，即使关闭浏览器再打开，也能记住上次的选择。
 *
 * ## 使用方式
 *
 * ```php
 * // 在 LiveComponent 中渲染
 * public function render(): Element
 * {
 *     return Element::make('div')->children(
 *         Element::make('h1')->intl('site.title'),
 *         new LanguageSwitcherLive()
 *     );
 * }
 * ```
 *
 * @view-category 国际化
 * @view-since 1.0.0
 */
class LanguageSwitcherLive extends LiveComponent
{
    /**
     * 当前语言，使用 LocalStorage 持久化
     */
    #[Persistent('local')]
    public string $locale = 'en';

    /**
     * 支持的语言列表
     */
    public array $locales = ['en', 'zh'];

    /**
     * 语言显示标签
     */
    public array $labels = [];

    public function mount(): void
    {
        // 如果有持久化的语言设置，使用它
        if ($this->locale && $this->locale !== 'en') {
            Translator::setLocale($this->locale);
        }

        // 设置默认标签
        if (empty($this->labels)) {
            $this->labels = [
                'en' => 'EN',
                'zh' => '中文',
            ];
        }
    }

    /**
     * 切换语言
     */
    #[LiveAction]
    public function switchLocale(string $locale): void
    {
        if (in_array($locale, $this->locales, true)) {
            $this->locale = $locale;
            Translator::setLocale($locale);
            $this->refresh('language-switcher');
        }
    }

    /**
     * 获取语言标签
     */
    private function getLabel(string $locale): string
    {
        return $this->labels[$locale] ?? strtoupper($locale);
    }

    public function render(): Element
    {
        $buttons = [];

        foreach ($this->locales as $locale) {
            $isActive = $locale === $this->locale;

            $btn = Element::make('button')
                ->attr('type', 'button')
                ->class('ux-lang-btn')
                ->class($isActive ? 'ux-lang-btn-active' : '')
                ->liveAction('switchLocale')
                ->data('locale', $locale)
                ->text($this->getLabel($locale));

            $buttons[] = $btn;
        }

        return Element::make('div')
            ->class('ux-lang-switcher')
            ->liveFragment('language-switcher')
            ->children(...$buttons);
    }
}
