<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\View\Base\Element;

/**
 * 下拉选择框
 *
 * 用于从预设选项中选择值，支持多选、空选项/占位符。
 *
 * @ux-category Form
 * @ux-since 1.0.0
 * @ux-example Select::make()->label('城市')->options(['Beijing' => '北京', 'Shanghai' => '上海'])->model('city')
 * @ux-example Select::make()->label('多选')->options(['A' => '选项A', 'B' => '选项B'])->multiple()
 * @ux-js-component —
 * @ux-css form.css
 */
class Select extends FormField
{
    protected array $options = [];
    protected bool $multiple = false;
    protected string $emptyOption = '';

    /**
     * 设置选项列表
     * @param array $options 选项数组 ['value' => '标签']
     * @return static
     * @ux-example Select::make()->options(['Beijing' => '北京', 'Shanghai' => '上海'])
     */
    public function options(array $options): static
    {
        $this->options = $options;
        return $this;
    }

    /**
     * 启用多选模式
     * @param bool $multiple 是否多选
     * @return static
     * @ux-example Select::make()->multiple()
     * @ux-default true
     */
    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;
        return $this;
    }

    /**
     * 设置空选项/占位符文字
     * @param string $text 空选项文字
     * @return static
     * @ux-example Select::make()->emptyOption('请选择...')
     */
    public function emptyOption(string $text): static
    {
        $this->emptyOption = $text;
        return $this;
    }

    /**
     * 设置占位符（等价于 emptyOption）
     * @param string $placeholder 占位提示
     * @return static
     * @ux-example Select::make()->placeholder('请选择...')
     */
    public function placeholder(string $placeholder): static
    {
        return $this->emptyOption($placeholder);
    }

    protected function toElement(): Element
    {
        $groupEl = Element::make('div')->class('ux-form-group');

        $labelEl = $this->renderLabel();
        if ($labelEl) {
            $groupEl->child($labelEl);
        }

        $selectEl = Element::make('select')
            ->class('ux-form-input');

        foreach ($this->buildFieldAttrs() as $key => $value) {
            $selectEl->attr($key, $value);
        }

        if ($this->multiple) {
            $selectEl->attr('multiple', '');
        }

        if ($this->emptyOption) {
            $selectEl->child(
                Element::make('option')
                    ->attr('value', '')
                    ->text($this->emptyOption)
            );
        }

        foreach ($this->options as $value => $label) {
            $optionEl = Element::make('option')
                ->attr('value', (string)$value)
                ->text($label);

            if ($this->value !== null && (string)$this->value === (string)$value) {
                $optionEl->attr('selected', '');
            }

            $selectEl->child($optionEl);
        }

        $groupEl->child($selectEl);

        $helpEl = $this->renderHelp();
        if ($helpEl) {
            $groupEl->child($helpEl);
        }

        return $groupEl;
    }
}
