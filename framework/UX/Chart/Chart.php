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
    protected static ?string $componentName = 'chart';

    protected string $type = 'line';
    protected array $data = [];
    protected array $options = [];
    protected ?string $title = null;
    protected ?string $description = null;
    protected int $height = 300;
    protected bool $showLegend = true;
    protected bool $showGrid = true;
    protected string $animation = 'none';

    protected function init(): void
    {
        $this->registerJs('chart', '
            const Chart = {
                charts: new Map(),
                init() {
                    document.querySelectorAll(".ux-chart[data-chart-id]").forEach(el => {
                        if (!this.charts.has(el.dataset.chartId)) this.initChart(el);
                    });
                },
                initChart(el) {
                    const chartId = el.dataset.chartId;
                    const type = el.dataset.chartType || "line";
                    const data = JSON.parse(el.dataset.chartData || "[]");
                    const options = JSON.parse(el.dataset.chartOptions || "{}");
                    const canvas = el.querySelector("canvas");
                    if (!canvas) return;
                    const ctx = canvas.getContext("2d");
                    if (!ctx) return;
                    this.drawChart(ctx, type, data, options, canvas);
                    this.charts.set(chartId, { el, canvas, ctx, type, data, options });
                },
                drawChart(ctx, type, data, options, canvas) {
                    const width = canvas.width = canvas.offsetWidth || 600;
                    const height = canvas.height = canvas.offsetHeight || 300;
                    ctx.clearRect(0, 0, width, height);
                    if (type === "line" || type === "area") this.drawLineChart(ctx, data, options, width, height, type === "area");
                    else if (type === "bar") this.drawBarChart(ctx, data, options, width, height);
                    else if (type === "pie" || type === "doughnut") this.drawPieChart(ctx, data, options, width, height, type === "doughnut");
                },
                drawLineChart(ctx, data, options, width, height, fill = false) {
                    const padding = 40;
                    const labels = data.labels || [];
                    const datasets = data.datasets || [];
                    if (!datasets.length) return;
                    const maxValue = Math.max(...datasets.flatMap(d => d.data || [])) || 1;
                    const chartWidth = width - padding * 2;
                    const chartHeight = height - padding * 2;
                    ctx.strokeStyle = "#e5e7eb";
                    ctx.lineWidth = 1;
                    for (let i = 0; i <= 5; i++) {
                        const y = padding + chartHeight - (i / 5) * chartHeight;
                        ctx.beginPath();
                        ctx.moveTo(padding, y);
                        ctx.lineTo(width - padding, y);
                        ctx.stroke();
                    }
                    datasets.forEach((dataset, di) => {
                        const color = dataset.borderColor || dataset.backgroundColor || "#3b82f6";
                        ctx.strokeStyle = color;
                        ctx.fillStyle = color + "20";
                        ctx.lineWidth = 2;
                        ctx.beginPath();
                        const points = (dataset.data || []).map((value, i) => ({
                            x: padding + (i / (labels.length - 1 || 1)) * chartWidth,
                            y: padding + chartHeight - (value / maxValue) * chartHeight
                        }));
                        if (points.length) {
                            ctx.moveTo(points[0].x, points[0].y);
                            points.forEach(p => ctx.lineTo(p.x, p.y));
                            ctx.stroke();
                            if (fill) {
                                ctx.lineTo(points[points.length - 1].x, padding + chartHeight);
                                ctx.lineTo(points[0].x, padding + chartHeight);
                                ctx.closePath();
                                ctx.fill();
                            }
                        }
                    });
                },
                drawBarChart(ctx, data, options, width, height) {
                    const padding = 40;
                    const labels = data.labels || [];
                    const datasets = data.datasets || [];
                    if (!datasets.length) return;
                    const maxValue = Math.max(...datasets.flatMap(d => d.data || [])) || 1;
                    const chartWidth = width - padding * 2;
                    const chartHeight = height - padding * 2;
                    const barCount = labels.length;
                    const groupWidth = chartWidth / barCount;
                    const barWidth = groupWidth / (datasets.length + 1);
                    datasets.forEach((dataset, di) => {
                        const color = dataset.backgroundColor || dataset.borderColor || "#3b82f6";
                        ctx.fillStyle = color;
                        (dataset.data || []).forEach((value, i) => {
                            const barHeight = (value / maxValue) * chartHeight;
                            const x = padding + i * groupWidth + di * barWidth + barWidth / 2;
                            const y = padding + chartHeight - barHeight;
                            ctx.fillRect(x, y, barWidth * 0.8, barHeight);
                        });
                    });
                },
                drawPieChart(ctx, data, options, width, height, doughnut = false) {
                    const centerX = width / 2;
                    const centerY = height / 2;
                    const radius = Math.min(width, height) / 3;
                    const datasets = data.datasets || [];
                    if (!datasets.length) return;
                    const values = datasets[0].data || [];
                    const colors = datasets[0].backgroundColor || ["#3b82f6", "#ef4444", "#10b981", "#f59e0b", "#8b5cf6", "#ec4899"];
                    const total = values.reduce((a, b) => a + b, 0) || 1;
                    let currentAngle = -Math.PI / 2;
                    values.forEach((value, i) => {
                        const angle = (value / total) * Math.PI * 2;
                        ctx.beginPath();
                        ctx.moveTo(centerX, centerY);
                        ctx.arc(centerX, centerY, radius, currentAngle, currentAngle + angle);
                        ctx.closePath();
                        ctx.fillStyle = colors[i % colors.length];
                        ctx.fill();
                        currentAngle += angle;
                    });
                    if (doughnut) {
                        ctx.beginPath();
                        ctx.arc(centerX, centerY, radius * 0.5, 0, Math.PI * 2);
                        ctx.fillStyle = "#ffffff";
                        ctx.fill();
                    }
                },
                update(chartId, data) {
                    const chart = this.charts.get(chartId);
                    if (chart) {
                        chart.data = data;
                        this.drawChart(chart.ctx, chart.type, data, chart.options, chart.canvas);
                    }
                },
                destroy(chartId) {
                    const chart = this.charts.get(chartId);
                    if (chart) {
                        this.charts.delete(chartId);
                    }
                }
            };
            return Chart;
        ');
    }

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