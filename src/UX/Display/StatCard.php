<?php

declare(strict_types=1);

namespace Framework\UX\Display;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 统计卡片
 *
 * 用于展示关键指标数据，支持标题、数值、描述、图标、趋势、变体、点击交互。
 *
 * @ux-category Display
 * @ux-since 1.0.0
 * @ux-example StatCard::make()->title('总收入')->value('¥12,345')->trendUp('12%')->icon('bi-currency-yen')
 * @ux-example StatCard::make()->title('用户数')->value('1,234')->description('较上月增长')->clickable()->clickAction('showDetails')
 * @ux-js-component stat-card.js
 * @ux-css stat-card.css
 */
class StatCard extends UXComponent
{
    protected string $title = '';
    protected string $value = '';
    protected ?string $description = null;
    protected ?string $icon = null;
    protected ?string $trend = null;
    protected ?string $trendValue = null;
    protected string $variant = 'default';
    protected ?string $clickAction = null;
    protected string $clickEvent = 'click';
    protected array $clickParams = [];

    /**
     * 设置统计卡片标题
     * @param string $title 标题文字
     * @return static
     * @ux-example StatCard::make()->title('总收入')
     */
    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    /**
     * 设置统计数值
     * @param string $value 数值（支持格式化字符串）
     * @return static
     * @ux-example StatCard::make()->value('¥12,345')
     */
    public function value(string $value): static
    {
        $this->value = $value;
        return $this;
    }

    /**
     * 设置描述文字
     * @param string $description 描述文字
     * @return static
     * @ux-example StatCard::make()->description('较上月增长')
     */
    public function description(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    /**
     * 设置图标
     * @param string $icon 图标类名或 HTML
     * @return static
     * @ux-example StatCard::make()->icon('bi-currency-yen')
     */
    public function icon(string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * 设置上升趋势
     * @param string $value 增长值
     * @return static
     * @ux-example StatCard::make()->trendUp('12%')
     */
    public function trendUp(string $value): static
    {
        $this->trend = 'up';
        $this->trendValue = $value;
        return $this;
    }

    /**
     * 设置下降趋势
     * @param string $value 下降值
     * @return static
     * @ux-example StatCard::make()->trendDown('5%')
     */
    public function trendDown(string $value): static
    {
        $this->trend = 'down';
        $this->trendValue = $value;
        return $this;
    }

    /**
     * 设置卡片变体
     * @param string $variant 变体名
     * @return static
     * @ux-example StatCard::make()->variant('primary')
     * @ux-default 'default'
     */
    public function variant(string $variant): static
    {
        $this->variant = $variant;
        return $this;
    }

    /**
     * 设置点击事件绑定的动作
     * @param string $action Action 名称
     * @param string $event 触发事件
     * @return static
     * @ux-example StatCard::make()->clickAction('showDetails')
     * @ux-default event='click'
     */
    public function clickAction(string $action, string $event = 'click'): static
    {
        $this->clickAction = $action;
        $this->clickEvent = $event;
        return $this;
    }

    /**
     * 设置点击参数
     * @param array $params 参数数组
     * @return static
     * @ux-example StatCard::make()->clickParams(['id' => 1])
     */
    public function clickParams(array $params): static
    {
        $this->clickParams = $params;
        return $this;
    }

    /**
     * 设置为可点击状态
     * @param bool $clickable 是否可点击
     * @return static
     * @ux-example StatCard::make()->clickable()
     */
    public function clickable(bool $clickable = true): static
    {
        if ($clickable && !$this->clickAction) {
            $this->clickAction = 'click';
        }
        return $this;
    }

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-stat-card');
        $el->class("ux-stat-card-{$this->variant}");

        if ($this->clickAction) {
            $el->class('ux-stat-card-clickable');
            $el->liveAction($this->clickAction, $this->clickEvent);
            if (!empty($this->clickParams)) {
                $el->data('action-params', json_encode($this->clickParams, JSON_UNESCAPED_UNICODE));
            }
        }

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
