<?php

declare(strict_types=1);

namespace Framework\UX\Chart;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class Chart extends UXComponent
{
    protected string $type = 'line';
    protected array $data = [];
    protected array $options = [];
    protected ?string $title = null;
    protected ?string $description = null;
    protected int $height = 300;
    protected bool $showLegend = true;
    protected bool $showGrid = true;
    protected string $animation = 'none';

    public function type(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function labels(array $labels): static
    {
        $this->data['labels'] = $labels;
        return $this;
    }

    public function dataset(string $label, array $data, array $options = []): static
    {
        $dataset = array_merge([
            'label' => $label,
            'data' => $data,
        ], $options);
        $this->data['datasets'][] = $dataset;
        return $this;
    }

    public function chartData(array $chartData): static
    {
        $this->data = array_merge($this->data, $chartData);
        return $this;
    }

    public function options(array $options): static
    {
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function description(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function height(int $height): static
    {
        $this->height = $height;
        return $this;
    }

    public function showLegend(bool $show = true): static
    {
        $this->showLegend = $show;
        return $this;
    }

    public function showGrid(bool $show = true): static
    {
        $this->showGrid = $show;
        return $this;
    }

    public function animation(string $animation): static
    {
        $this->animation = $animation;
        return $this;
    }

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-chart');
        $el->attr('data-ux-chart', $this->type);

        $chartConfig = [
            'type' => $this->type,
            'data' => $this->data,
            'options' => array_merge([
                'showLegend' => $this->showLegend,
                'showGrid' => $this->showGrid,
                'animation' => $this->animation,
            ], $this->options),
        ];

        $el->data('chart-config', json_encode($chartConfig, JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT | JSON_HEX_AMP));
        $el->style("height: {$this->height}px");

        if ($this->title || $this->description) {
            $headerEl = Element::make('div')->class('ux-chart-header');

            if ($this->title) {
                $headerEl->child(Element::make('h3')->class('ux-chart-title')->text($this->title));
            }

            if ($this->description) {
                $headerEl->child(Element::make('p')->class('ux-chart-description')->text($this->description));
            }

            $el->child($headerEl);
        }

        $canvasContainer = Element::make('div')->class('ux-chart-canvas-container');
        $canvasContainer->child(
            Element::make('canvas')
                ->class('ux-chart-canvas')
                ->attr('id', "{$this->id}-canvas")
        );
        $el->child($canvasContainer);

        return $el;
    }
}