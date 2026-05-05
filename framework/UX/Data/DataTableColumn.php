<?php

declare(strict_types=1);

namespace Framework\UX\Data;

/**
 * 数据表格列配置
 *
 * 用于配置 DataTable 的列，支持宽度、对齐、排序、固定、可见性、提示、渲染、搜索。
 *
 * @ux-category Data
 * @ux-since 1.0.0
 * @ux-example DataTableColumn::make('name', '姓名')->sortable()->alignCenter()
 * @ux-example DataTableColumn::make('action', '操作')->render(fn($row) => Button::make()->label('编辑')->liveAction('edit', $row['id']))
 * @ux-example DataTableColumn::make('created_at', '创建时间')->searchable()->searchLike()
 */
class DataTableColumn
{
    public string $dataKey;
    public string $title;
    public ?\Closure $render = null;
    public ?string $width = null;
    public ?string $align = null;
    public bool $sortable = false;
    public ?string $fixed = null;
    public bool $visible = true;
    public ?string $tooltip = null;

    public bool $searchable = false;
    public string $searchType = 'like';
    public ?array $searchOptions = null;

    public function __construct(string $dataKey, string $title)
    {
        $this->dataKey = $dataKey;
        $this->title = $title;
    }

    public static function make(string $dataKey, string $title): static
    {
        return new static($dataKey, $title);
    }

    /**
     * 设置列宽
     * @param string|null $width 宽度（如 '100px'、'15%'）
     * @return static
     */
    public function width(?string $width): static
    {
        $this->width = $width;
        return $this;
    }

    /**
     * 设置列对齐方式
     * @param string|null $align 对齐：left/center/right
     * @return static
     */
    public function align(?string $align): static
    {
        $this->align = $align;
        return $this;
    }

    /**
     * 居中对齐
     * @return static
     * @ux-example DataTableColumn::make('name', '姓名')->alignCenter()
     */
    public function alignCenter(): static
    {
        return $this->align('center');
    }

    /**
     * 右对齐
     * @return static
     * @ux-example DataTableColumn::make('price', '价格')->alignRight()
     */
    public function alignRight(): static
    {
        return $this->align('right');
    }

    /**
     * 左对齐
     * @return static
     * @ux-example DataTableColumn::make('name', '姓名')->alignLeft()
     */
    public function alignLeft(): static
    {
        return $this->align('left');
    }

    /**
     * 设置是否可排序
     * @param bool $sortable 是否可排序
     * @return static
     * @ux-default false
     */
    public function sortable(bool $sortable = true): static
    {
        $this->sortable = $sortable;
        return $this;
    }

    /**
     * 设置列固定位置
     * @param string|null $position 位置：left/right
     * @return static
     */
    public function fixed(?string $position): static
    {
        $this->fixed = $position;
        return $this;
    }

    /**
     * 固定在左侧
     * @return static
     * @ux-example DataTableColumn::make('name', '姓名')->fixedLeft()
     */
    public function fixedLeft(): static
    {
        return $this->fixed('left');
    }

    /**
     * 固定在右侧
     * @return static
     * @ux-example DataTableColumn::make('action', '操作')->fixedRight()
     */
    public function fixedRight(): static
    {
        return $this->fixed('right');
    }

    /**
     * 设置列是否可见
     * @param bool $visible 是否可见
     * @return static
     * @ux-default true
     */
    public function visible(bool $visible = true): static
    {
        $this->visible = $visible;
        return $this;
    }

    /**
     * 隐藏列
     * @return static
     * @ux-example DataTableColumn::make('internal_id', '内部ID')->hidden()
     */
    public function hidden(): static
    {
        return $this->visible(false);
    }

    /**
     * 设置列提示文字
     * @param string|null $tooltip 提示文字
     * @return static
     */
    public function tooltip(?string $tooltip): static
    {
        $this->tooltip = $tooltip;
        return $this;
    }

    /**
     * 自定义列渲染
     * @param \Closure $callback 回调函数，接收 ($value, $row, $index)
     * @return static
     * @ux-example DataTableColumn::make('action', '操作')->render(fn($row) => Button::make()->label('编辑')->liveAction('edit', $row['id']))
     */
    public function render(\Closure $callback): static
    {
        $this->render = $callback;
        return $this;
    }

    /**
     * 设置列是否可搜索
     * @param bool $searchable 是否可搜索
     * @param string $type 搜索类型：like/=/in
     * @param array|null $options 搜索选项
     * @return static
     * @ux-default searchable=false
     */
    public function searchable(bool $searchable = true, string $type = 'like', ?array $options = null): static
    {
        $this->searchable = $searchable;
        $this->searchType = $type;
        $this->searchOptions = $options;
        return $this;
    }

    /**
     * 精确搜索（=）
     * @return static
     * @ux-example DataTableColumn::make('status', '状态')->searchEqual()
     */
    public function searchEqual(): static
    {
        return $this->searchable(true, '=');
    }

    /**
     * 模糊搜索（like）
     * @return static
     * @ux-default searchType='like'
     */
    public function searchLike(): static
    {
        return $this->searchable(true, 'like');
    }

    /**
     * IN 搜索（多选）
     * @param array|null $options 选项数组
     * @return static
     * @ux-example DataTableColumn::make('status', '状态')->searchIn(['待处理', '进行中', '已完成'])
     */
    public function searchIn(?array $options = null): static
    {
        return $this->searchable(true, 'in', $options);
    }

    public function toArray(): array
    {
        return [
            'dataKey' => $this->dataKey,
            'title' => $this->title,
            'render' => $this->render,
            'width' => $this->width,
            'align' => $this->align,
            'sortable' => $this->sortable,
            'fixed' => $this->fixed,
            'visible' => $this->visible,
            'tooltip' => $this->tooltip,
            'searchable' => $this->searchable,
            'searchType' => $this->searchType,
            'searchOptions' => $this->searchOptions,
        ];
    }
}
