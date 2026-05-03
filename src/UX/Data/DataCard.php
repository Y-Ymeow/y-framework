<?php

declare(strict_types=1);

namespace Framework\UX\Data;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 数据卡片
 *
 * 用于展示单条数据的卡片视图，支持字段定义、标题、副标题、头像、封面、操作区。
 *
 * @ux-category Data
 * @ux-since 1.0.0
 * @ux-example DataCard::make()->title('用户信息')->field('姓名', 'name')->field('邮箱', 'email')->dataSource($user)
 * @ux-example DataCard::make()->cover('/cover.jpg')->avatar($avatar)->title('详情')->fields($fields)->item($data)
 * @ux-js-component data-card.js
 * @ux-css data-card.css
 */
class DataCard extends UXComponent
{
    protected array $fields = [];
    protected array $dataSource = [];
    protected string $variant = 'default';
    protected ?string $title = null;
    protected ?string $subtitle = null;
    protected mixed $avatar = null;
    protected mixed $actions = null;
    protected mixed $cover = null;
    protected bool $bordered = false;
    protected ?string $fragmentName = null;

    /**
     * 添加一个字段
     * @param string $label 字段标签
     * @param string $dataKey 数据键名
     * @param \Closure|null $render 自定义渲染回调
     * @param array $options 额外选项（span 等）
     * @return static
     * @ux-example DataCard::make()->field('姓名', 'name')->field('邮箱', 'email')
     */
    public function field(string $label, string $dataKey, ?\Closure $render = null, array $options = []): static
    {
        $this->fields[] = array_merge([
            'label' => $label,
            'dataKey' => $dataKey,
            'render' => $render,
            'span' => null,
        ], $options);
        return $this;
    }

    /**
     * 批量添加字段
     * @param array $fields 字段配置数组
     * @return static
     */
    public function fields(array $fields): static
    {
        foreach ($fields as $field) {
            $this->field(
                $field['label'] ?? '',
                $field['dataKey'] ?? $field['key'] ?? '',
                $field['render'] ?? null,
                $field
            );
        }
        return $this;
    }

    /**
     * 设置数据源
     * @param array $data 数据数组
     * @return static
     */
    public function dataSource(array $data): static
    {
        $this->dataSource = $data;
        return $this;
    }

    /**
     * 设置数据项（别名）
     * @param array $data 数据数组
     * @return static
     */
    public function item(array $data): static
    {
        return $this->dataSource($data);
    }

    /**
     * 设置变体
     * @param string $variant 变体：default
     * @return static
     * @ux-default 'default'
     */
    public function variant(string $variant): static
    {
        $this->variant = $variant;
        return $this;
    }

    /**
     * 设置卡片标题
     * @param string $title 标题
     * @return static
     */
    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    /**
     * 设置卡片副标题
     * @param string $subtitle 副标题
     * @return static
     */
    public function subtitle(string $subtitle): static
    {
        $this->subtitle = $subtitle;
        return $this;
    }

    /**
     * 设置头像内容
     * @param mixed $avatar 头像（Element 或组件或 URL）
     * @return static
     */
    public function avatar(mixed $avatar): static
    {
        $this->avatar = $avatar;
        return $this;
    }

    /**
     * 设置操作区内容
     * @param mixed $actions 操作区内容（Element 或组件）
     * @return static
     */
    public function actions(mixed $actions): static
    {
        $this->actions = $actions;
        return $this;
    }

    /**
     * 设置封面图
     * @param mixed $cover 封面（Element 或组件或 URL）
     * @return static
     */
    public function cover(mixed $cover): static
    {
        $this->cover = $cover;
        return $this;
    }

    /**
     * 设置是否带边框
     * @param bool $bordered 是否带边框
     * @return static
     * @ux-default false
     */
    public function bordered(bool $bordered = true): static
    {
        $this->bordered = $bordered;
        return $this;
    }

    /**
     * 设置分片名称（用于 Live 局部更新）
     * @param string $name 分片名
     * @return static
     */
    public function fragment(string $name): static
    {
        $this->fragmentName = $name;
        return $this;
    }

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);
        $el->class('ux-data-card');
        $el->class("ux-data-card-{$this->variant}");

        if ($this->bordered) {
            $el->class('ux-data-card-bordered');
        }

        if ($this->fragmentName) {
            $el->liveFragment($this->fragmentName);
        }

        if ($this->cover) {
            $coverEl = Element::make('div')->class('ux-data-card-cover');
            $coverEl->child($this->resolveChild($this->cover));
            $el->child($coverEl);
        }

        $headerEl = Element::make('div')->class('ux-data-card-header');
        $hasHeader = false;

        if ($this->avatar) {
            $avatarEl = Element::make('div')->class('ux-data-card-avatar');
            $avatarEl->child($this->resolveChild($this->avatar));
            $headerEl->child($avatarEl);
            $hasHeader = true;
        }

        if ($this->title || $this->subtitle) {
            $titleWrapper = Element::make('div')->class('ux-data-card-title-wrapper');
            if ($this->title) {
                $titleText = is_string($this->title) && isset($this->dataSource[$this->title])
                    ? (string)$this->dataSource[$this->title]
                    : $this->title;
                $titleWrapper->child(Element::make('div')->class('ux-data-card-title')->text($titleText));
            }
            if ($this->subtitle) {
                $subtitleText = is_string($this->subtitle) && isset($this->dataSource[$this->subtitle])
                    ? (string)$this->dataSource[$this->subtitle]
                    : $this->subtitle;
                $titleWrapper->child(Element::make('div')->class('ux-data-card-subtitle')->text($subtitleText));
            }
            $headerEl->child($titleWrapper);
            $hasHeader = true;
        }

        if ($this->actions) {
            $actionsEl = Element::make('div')->class('ux-data-card-actions');
            $actionsEl->child($this->resolveChild($this->actions));
            $headerEl->child($actionsEl);
            $hasHeader = true;
        }

        if ($hasHeader) {
            $el->child($headerEl);
        }

        if (!empty($this->fields)) {
            $bodyEl = Element::make('div')->class('ux-data-card-body');

            foreach ($this->fields as $field) {
                $fieldEl = Element::make('div')->class('ux-data-card-field');

                $labelEl = Element::make('div')
                    ->class('ux-data-card-field-label')
                    ->text($field['label']);
                $fieldEl->child($labelEl);

                $value = $this->dataSource[$field['dataKey']] ?? null;
                $valueEl = Element::make('div')->class('ux-data-card-field-value');

                if (isset($field['render']) && $field['render'] instanceof \Closure) {
                    $rendered = ($field['render'])($value, $this->dataSource);
                    if ($rendered instanceof Element || $rendered instanceof UXComponent) {
                        $valueEl->child($this->resolveChild($rendered));
                    } elseif (is_string($rendered)) {
                        $valueEl->html($rendered);
                    } else {
                        $valueEl->text((string)($value ?? '-'));
                    }
                } else {
                    $valueEl->text((string)($value ?? '-'));
                }

                $fieldEl->child($valueEl);
                $bodyEl->child($fieldEl);
            }

            $el->child($bodyEl);
        }

        $this->appendChildren($el);

        return $el;
    }
}
