<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 颜色选择器
 *
 * 用于颜色选择，支持颜色值显示、清除、禁用、预设颜色、Live 绑定。
 *
 * @ux-category Form
 * @ux-since 1.0.0
 * @ux-example ColorPicker::make()->value('#3b82f6')->label('主题色')
 * @ux-example ColorPicker::make()->value('#ef4444')->presets(['#ef4444', '#3b82f6', '#10b981'])
 * @ux-js-component color-picker.js
 * @ux-css color-picker.css
 */
class ColorPicker extends UXComponent
{
    protected static ?string $componentName = 'colorPicker';

    protected ?string $value = null;
    protected bool $allowClear = false;
    protected bool $disabled = false;
    protected bool $showText = true;
    protected ?string $action = null;
    protected array $presets = [];

    protected function init(): void
    {
        $this->registerJs('colorPicker', '
            const ColorPicker = {
                activePicker: null,
                init() {
                    // 初始化所有颜色选择器：同步默认值到显示
                    document.querySelectorAll(".ux-color-picker").forEach(picker => {
                        const color = picker.dataset.colorValue;
                        if (color) {
                            const block = picker.querySelector(".ux-color-picker-block");
                            if (block) block.style.backgroundColor = color;
                            const text = picker.querySelector(".ux-color-picker-text");
                            if (text) text.textContent = color.toUpperCase();
                            const input = picker.querySelector(".ux-color-picker-input");
                            if (input && !input.value) input.value = color;
                        }
                    });

                    document.addEventListener("click", (e) => {
                        const preview = e.target.closest(".ux-color-picker-preview");
                        if (preview) {
                            const picker = preview.closest(".ux-color-picker");
                            if (picker && !picker.classList.contains("ux-color-picker-disabled")) {
                                this.toggle(picker);
                            }
                        }
                        const clear = e.target.closest(".ux-color-picker-clear");
                        if (clear) {
                            const picker = clear.closest(".ux-color-picker");
                            if (picker) {
                                e.stopPropagation();
                                this.clear(picker);
                            }
                        }
                        const preset = e.target.closest(".ux-color-picker-preset");
                        if (preset) {
                            const picker = preset.closest(".ux-color-picker");
                            const color = preset.dataset.color;
                            if (picker && color) {
                                this.setValue(picker, color);
                                this.hide(picker);
                            }
                        }
                        if (!e.target.closest(".ux-color-picker")) {
                            this.hideAll();
                        }
                    });
                    document.addEventListener("input", (e) => {
                        if (e.target.classList.contains("ux-color-picker-native")) {
                            const picker = e.target.closest(".ux-color-picker");
                            if (picker) this.setValue(picker, e.target.value);
                        }
                    });
                    document.addEventListener("change", (e) => {
                        if (e.target.classList.contains("ux-color-picker-custom-input")) {
                            const picker = e.target.closest(".ux-color-picker");
                            if (picker) {
                                let color = e.target.value;
                                if (!color.startsWith("#")) color = "#" + color;
                                if (this.isValidColor(color)) this.setValue(picker, color);
                            }
                        }
                    });
                },
                toggle(picker) {
                    if (this.activePicker === picker) this.hide(picker);
                    else this.show(picker);
                },
                show(picker) {
                    this.hideAll();
                    let dropdown = picker.querySelector(".ux-color-picker-dropdown");
                    if (!dropdown) dropdown = this.createDropdown(picker);
                    dropdown.classList.add("show");
                    this.activePicker = picker;
                },
                hide(picker) {
                    const dropdown = picker.querySelector(".ux-color-picker-dropdown");
                    if (dropdown) dropdown.classList.remove("show");
                    if (this.activePicker === picker) this.activePicker = null;
                },
                hideAll() {
                    document.querySelectorAll(".ux-color-picker-dropdown.show").forEach(d => d.classList.remove("show"));
                    this.activePicker = null;
                },
                createDropdown(picker) {
                    const presets = picker.dataset.colorPresets;
                    const currentValue = picker.dataset.colorValue || "#3b82f6";
                    const dropdown = document.createElement("div");
                    dropdown.className = "ux-color-picker-dropdown";
                    if (presets) {
                        const presetsContainer = document.createElement("div");
                        presetsContainer.className = "ux-color-picker-presets";
                        const colors = JSON.parse(presets);
                        colors.forEach(color => {
                            const preset = document.createElement("div");
                            preset.className = "ux-color-picker-preset";
                            preset.style.backgroundColor = color;
                            preset.dataset.color = color;
                            presetsContainer.appendChild(preset);
                        });
                        dropdown.appendChild(presetsContainer);
                    }
                    const customContainer = document.createElement("div");
                    customContainer.className = "ux-color-picker-custom";
                    const nativeInput = document.createElement("input");
                    nativeInput.type = "color";
                    nativeInput.value = currentValue;
                    nativeInput.className = "ux-color-picker-native";
                    const textInput = document.createElement("input");
                    textInput.type = "text";
                    textInput.value = currentValue;
                    textInput.className = "ux-color-picker-custom-input";
                    textInput.placeholder = "#000000";
                    customContainer.appendChild(nativeInput);
                    customContainer.appendChild(textInput);
                    dropdown.appendChild(customContainer);
                    picker.appendChild(dropdown);
                    return dropdown;
                },
                setValue(picker, color) {
                    picker.dataset.colorValue = color;
                    const block = picker.querySelector(".ux-color-picker-block");
                    if (block) block.style.backgroundColor = color;
                    const text = picker.querySelector(".ux-color-picker-text");
                    if (text) text.textContent = color.toUpperCase();
                    const input = picker.querySelector(".ux-color-picker-input");
                    if (input) input.value = color;
                    const customInput = picker.querySelector(".ux-color-picker-custom-input");
                    if (customInput) customInput.value = color.toUpperCase();
                    const nativeInput = picker.querySelector(".ux-color-picker-native");
                    if (nativeInput) nativeInput.value = color;
                    picker.dispatchEvent(new CustomEvent("ux:change", { detail: { value: color }, bubbles: true }));
                },
                clear(picker) {
                    this.setValue(picker, "");
                    const clear = picker.querySelector(".ux-color-picker-clear");
                    if (clear) clear.style.display = "none";
                },
                isValidColor(color) {
                    return /^#[0-9A-Fa-f]{6}$/.test(color);
                },
                setColor(id, color) {
                    const picker = document.querySelector(`#${id}.ux-color-picker`);
                    if (picker) this.setValue(picker, color);
                },
                getColor(id) {
                    const picker = document.querySelector(`#${id}.ux-color-picker`);
                    return picker ? picker.dataset.colorValue : null;
                }
            };
            return ColorPicker;
        ');
    }

    /**
     * 设置颜色值
     * @param string $value 颜色值（如 #3b82f6）
     * @return static
     * @ux-example ColorPicker::make()->value('#ef4444')
     */
    public function value(string $value): static
    {
        $this->value = $value;
        return $this;
    }

    /**
     * 启用清除按钮
     * @param bool $allow 是否允许清除
     * @return static
     * @ux-example ColorPicker::make()->allowClear()
     * @ux-default true
     */
    public function allowClear(bool $allow = true): static
    {
        $this->allowClear = $allow;
        return $this;
    }

    /**
     * 设置禁用状态
     * @param bool $disabled 是否禁用
     * @return static
     * @ux-example ColorPicker::make()->disabled()
     * @ux-default true
     */
    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;
        return $this;
    }

    /**
     * 显示颜色值文本
     * @param bool $show 是否显示
     * @return static
     * @ux-example ColorPicker::make()->showText(false)
     * @ux-default true
     */
    public function showText(bool $show = true): static
    {
        $this->showText = $show;
        return $this;
    }

    /**
     * 设置 LiveAction（选择颜色时触发）
     * @param string $action Action 名称
     * @return static
     * @ux-example ColorPicker::make()->action('updateColor')
     */
    public function action(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    /**
     * 设置预设颜色列表
     * @param array $presets 预设颜色数组
     * @return static
     * @ux-example ColorPicker::make()->presets(['#ef4444', '#3b82f6', '#10b981'])
     */
    public function presets(array $presets): static
    {
        $this->presets = $presets;
        return $this;
    }

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-color-picker');
        if ($this->disabled) {
            $el->class('ux-color-picker-disabled');
        }

        $el->data('color-value', $this->value ?? '#3b82f6');

        if ($this->action) {
            $el->data('color-action', $this->action);
        }

        if (!empty($this->presets)) {
            $el->data('color-presets', json_encode($this->presets));
        }

        // 颜色预览区域
        $previewEl = Element::make('div')->class('ux-color-picker-preview');
        $colorBlock = Element::make('div')
            ->class('ux-color-picker-block')
            ->style('background-color: ' . ($this->value ?? '#3b82f6'));
        $previewEl->child($colorBlock);

        if ($this->showText) {
            $textEl = Element::make('span')
                ->class('ux-color-picker-text')
                ->text($this->value ?? '#3b82f6');
            $previewEl->child($textEl);
        }

        if ($this->allowClear && $this->value) {
            $clearEl = Element::make('span')
                ->class('ux-color-picker-clear')
                ->html('<i class="bi bi-x"></i>');
            $previewEl->child($clearEl);
        }

        $el->child($previewEl);

        // 隐藏的原生 input
        $inputEl = Element::make('input')
            ->attr('type', 'color')
            ->attr('value', $this->value ?? '#3b82f6')
            ->class('ux-color-picker-input');
        $el->child($inputEl);

        // Live 桥接隐藏 input
        $liveInput = $this->createLiveModelInput($this->value ?? '#3b82f6');
        if ($liveInput) {
            $el->child($liveInput);
        }

        return $el;
    }
}
