<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\View\Base\Element;

class SearchInput extends FormField
{
    protected string $endpoint = '';
    protected array $options = [];

    public function endpoint(string $url): static
    {
        $this->endpoint = $url;
        return $this;
    }

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
