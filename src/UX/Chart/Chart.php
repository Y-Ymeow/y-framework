<?php

declare(strict_types=1);

namespace Framework\UX\Chart;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 图表
 *
 * 用于渲染数据图表，支持多种图表类型、数据配置、响应式。
 *
 * @ux-category Chart
 * @ux-since 1.0.0
 * @ux-example Chart::make()->type('line')->data($data)->options(['responsive' => true])
 * @ux-example Chart::make()->type('bar')->dataset('销售', [10, 20, 30])->dataset('利润', [5, 15, 25])
 * @ux-js-component chart.js
 * @ux-css chart.css
 */
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

    /**
     * 设置图表类型
     * @param string $type 图表类型：line/bar/pie/doughnut/radar/polarArea/bubble/scatter
     * @return static
     * @ux-default 'line'
     */
    public function type(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    /**
     * 设置图表标签（X 轴）
     * @param array $labels 标签数组
     * @return static
     * @ux-example Chart::make()->type('bar')->labels(['一月', '二月', '三月'])
     */
    public function labels(array $labels): static
    {
        $this->data['labels'] = $labels;
        return $this;
    }

    /**
     * 添加数据集
     * @param string $label 数据集名称
     * @param array $data 数据数组
     * @param array $options 额外选项（backgroundColor, borderColor 等）
     * @return static
     * @ux-example Chart::make()->type('bar')->dataset('销售', [10, 20, 30], ['backgroundColor' => '#3b82f6'])
     */
    public function dataset(string $label, array $data, array $options = []): static
    {
        $dataset = array_merge([
            'label' => $label,
            'data' => $data,
        ], $options);
        $this->data['datasets'][] = $dataset;
        return $this;
    }

    /**
     * 设置完整图表数据（替代 labels + datasets）
     * @param array $chartData 完整数据配置
     * @return static
     * @ux-example Chart::make()->chartData(['labels' => [...], 'datasets' => [...]])
     */
    public function chartData(array $chartData): static
    {
        $this->data = array_merge($this->data, $chartData);
        return $this;
    }

    /**
     * 设置图表选项（底层 Chart.js 配置）
     * @param array $options 选项配置
     * @return static
     * @ux-example Chart::make()->options(['responsive' => true, 'plugins.title.text' => '图表标题'])
     */
    public function options(array $options): static
    {
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    /**
     * 设置图表标题
     * @param string $title 标题文本
     * @return static
     * @ux-example Chart::make()->title('月度销售统计')
     */
    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    /**
     * 设置图表描述/副标题
     * @param string $description 描述文本
     * @return static
     */
    public function description(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    /**
     * 设置图表高度
     * @param int $height 高度（像素）
     * @return static
     * @ux-default 300
     */
    public function height(int $height): static
    {
        $this->height = $height;
        return $this;
    }

    /**
     * 设置是否显示图例
     * @param bool $show 是否显示
     * @return static
     * @ux-default true
     */
    public function showLegend(bool $show = true): static
    {
        $this->showLegend = $show;
        return $this;
    }

    /**
     * 设置是否显示网格线
     * @param bool $show 是否显示
     * @return static
     * @ux-default true
     */
    public function showGrid(bool $show = true): static
    {
        $this->showGrid = $show;
        return $this;
    }

    /**
     * 设置动画效果
     * @param string $animation 动画类型：none/scale/fade/slide
     * @return static
     * @ux-default 'none'
     */
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