<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\View\Base\Element;

/**
 * 搜索输入框
 *
 * 用于搜索输入，支持远程搜索端点、本地选项、自动完成、结果展示。
 *
 * @ux-category Form
 * @ux-since 1.0.0
 * @ux-example SearchInput::make()->name('q')->label('搜索')->endpoint('/search/api')
 * @ux-example SearchInput::make()->name('keyword')->label('关键词')->options(['苹果', '香蕉', '橙子'])
 * @ux-js-component search-input.js
 * @ux-css form.css
 */
class SearchInput extends FormField
{
    protected string $endpoint = '';
    protected array $options = [];

    /**
     * 设置远程搜索端点
     * @param string $url 搜索 API 地址
     * @return static
     * @ux-example SearchInput::make()->endpoint('/search/api')
     */
    public function endpoint(string $url): static
    {
        $this->endpoint = $url;
        return $this;
    }

    /**
     * 设置本地选项列表（用于自动完成）
     * @param array $options 选项数组
     * @return static
     * @ux-example SearchInput::make()->options(['苹果', '香蕉', '橙子'])
     */
    public function options(array $options): static
    {
        $this->options = $options;
        return $this;
    }

    protected function toElement(): Element
    {
        $groupEl = Element::make('div')->class('ux-form-group');

        $searchEl = Element::make('div')->class('ux-search');
        $searchEl->data('search', 'true');

        if ($this->endpoint) {
            $searchEl->data('endpoint', $this->endpoint);
        }

        $inputEl = Element::make('input')
            ->attr('type', 'search')
            ->class('ux-form-input')
            ->class('ux-search-input')
            ->attr('autocomplete', 'off');

        foreach ($this->buildFieldAttrs() as $key => $value) {
            $inputEl->attr($key, $value);
        }

        if ($this->value !== null) {
            $inputEl->attr('value', (string)$this->value);
        }

        $searchEl->child($inputEl);
        $searchEl->child(Element::make('span')->class('ux-search-icon')->text('🔍'));
        $searchEl->child(Element::make('div')->class('ux-search-results'));

        $groupEl->child($searchEl);

        $helpEl = $this->renderHelp();
        if ($helpEl) {
            $groupEl->child($helpEl);
        }

        return $groupEl;
    }
}
