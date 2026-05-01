<?php

declare(strict_types=1);

namespace Framework\UX\Display;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class StatCard extends UXComponent
{
    protected string $title = '';
    protected string $value = '';
    protected ?string $description = null;
    protected ?string $icon = null;
    protected ?string $trend = null;
    protected ?string $trendValue = null;
    protected string $variant = 'default';

    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function value(string $value): static
    {
        $this->value = $value;
        return $this;
    }

    public function description(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function trendUp(string $value): static
    {
        $this->trend = 'up';
        $this->trendValue = $value;
        return $this;
    }

    public function trendDown(string $value): static
    {
        $this->trend = 'down';
        $this->trendValue = $value;
        return $this;
    }

    public function variant(string $variant): static
    {
        $this->variant = $variant;
        return $this;
    }

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-stat-card');
        $el->class("ux-stat-card-{$this->variant}");

        $bodyEl = Element::make('div')->class('ux-stat-card-body');

        $headerEl = Element::make('div')->class('ux-stat-card-header');
        $headerEl->child(Element::make('span')->class('ux-stat-card-title')->text($this->title));
        if ($this->icon) {
            $headerEl->child(Element::make('span')->class('ux-stat-card-icon')->html($this->icon));
        }
        $bodyEl->child($headerEl);

        $bodyEl->child(
            Element::make('div')
                ->class('ux-stat-card-value')
                ->text($this->value)
        );

        if ($this->trend || $this->description) {
            $footerEl = Element::make('div')->class('ux-stat-card-footer');

            if ($this->trend) {
                $trendClass = "ux-trend-{$this->trend}";
                $trendIcon = $this->trend === 'up' ? '↑' : '↓';
                $footerEl->child(
                    Element::make('span')
                        ->class('ux-stat-card-trend')
                        ->class($trendClass)
                        ->text("{$trendIcon} {$this->trendValue}")
                );
            }

            if ($this->description) {
                $footerEl->child(
                    Element::make('span')
                        ->class('ux-stat-card-desc')
                        ->text($this->description)
                );
            }

            $bodyEl->child($footerEl);
        }

        $el->child($bodyEl);

        return $el;
    }
}
