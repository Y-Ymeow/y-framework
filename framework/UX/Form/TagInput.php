<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 标签输入框
 *
 * 用于多标签输入，支持增删标签、占位符、最大数量限制、清除功能、Live 绑定。
 *
 * @ux-category Form
 * @ux-since 1.0.0
 * @ux-example TagInput::make()->value(['PHP', 'Laravel', 'Vue'])
 * @ux-example TagInput::make()->placeholder('输入标签后按回车')->maxCount(5)->allowClear()
 * @ux-js-component tag-input.js
 * @ux-css tag-input.css
 */
class TagInput extends UXComponent
{
    protected static ?string $componentName = 'tagInput';

    protected array $value = [];
    protected ?string $placeholder = '请输入标签';
    protected bool $disabled = false;
    protected int $maxCount = 0;
    protected ?string $action = null;
    protected bool $allowClear = true;

    protected function init(): void
    {
        $this->registerJs('tagInput', '
            const TagInput = {
                init() {
                    // 初始化所有标签输入：渲染默认值标签
                    document.querySelectorAll(".ux-tag-input").forEach(tagInput => {
                        const valueStr = tagInput.dataset.tagValue;
                        if (valueStr) {
                            try {
                                const values = JSON.parse(valueStr);
                                if (Array.isArray(values)) {
                                    values.forEach(value => {
                                        if (value && typeof value === "string") this.addTag(tagInput, value);
                                    });
                                }
                            } catch (e) {
                                // 兼容逗号分隔的字符串
                                valueStr.split(",").forEach(value => {
                                    const v = value.trim();
                                    if (v) this.addTag(tagInput, v);
                                });
                            }
                        }
                    });

                    document.addEventListener("keydown", (e) => {
                        if (!e.target || !e.target.classList) return;
                        const input = e.target.closest(".ux-tag-input-field");
                        if (!input) return;
                        const tagInput = input.closest(".ux-tag-input");
                        if (!tagInput) return;
                        if (e.key === "Enter") {
                            e.preventDefault();
                            const value = input.value.trim();
                            if (value) {
                                this.addTag(tagInput, value);
                                input.value = "";
                            }
                        } else if (e.key === "Backspace" && !input.value) {
                            const tags = tagInput.querySelectorAll(".ux-tag-input-tag");
                            if (tags.length > 0) this.removeTag(tags[tags.length - 1]);
                        }
                    });
                    document.addEventListener("click", (e) => {
                        if (!e.target || !e.target.closest) return;
                        const removeBtn = e.target.closest(".ux-tag-input-tag-remove");
                        if (removeBtn) {
                            const tag = removeBtn.closest(".ux-tag-input-tag");
                            if (tag) this.removeTag(tag);
                        }
                        const container = e.target.closest(".ux-tag-input-container");
                        if (container) {
                            const input = container.querySelector(".ux-tag-input-field");
                            if (input) input.focus();
                        }
                    });
                },
                addTag(tagInput, value) {
                    const maxCount = parseInt(tagInput.dataset.tagMax) || 0;
                    const currentTags = tagInput.querySelectorAll(".ux-tag-input-tag");
                    if (maxCount > 0 && currentTags.length >= maxCount) return;
                    const container = tagInput.querySelector(".ux-tag-input-container");
                    const input = tagInput.querySelector(".ux-tag-input-field");
                    const tag = document.createElement("span");
                    tag.className = "ux-tag-input-tag";
                    tag.innerHTML = `${value}<span class="ux-tag-input-tag-remove"><i class="bi bi-x"></i></span>`;
                    container.insertBefore(tag, input);
                    this.updateValue(tagInput);
                },
                removeTag(tag) {
                    const tagInput = tag.closest(".ux-tag-input");
                    tag.remove();
                    if (tagInput) this.updateValue(tagInput);
                },
                updateValue(tagInput) {
                    const tags = tagInput.querySelectorAll(".ux-tag-input-tag");
                    const values = Array.from(tags).map(tag => tag.childNodes[0].textContent.trim());
                    tagInput.dataset.tagValue = JSON.stringify(values);
                    const hidden = tagInput.querySelector(".ux-tag-input-hidden");
                    if (hidden) hidden.value = values.join(",");
                    tagInput.dispatchEvent(new CustomEvent("ux:change", { detail: { value: values.join(",") }, bubbles: true }));
                }
            };
            return TagInput;
        ');
    }

    /**
     * 设置标签值列表
     * @param array $value 标签数组
     * @return static
     * @ux-example TagInput::make()->value(['PHP', 'Laravel', 'Vue'])
     */
    public function value(array $value): static
    {
        $this->value = $value;
        return $this;
    }

    /**
     * 设置占位文本
     * @param string $placeholder 占位提示
     * @return static
     * @ux-example TagInput::make()->placeholder('输入标签后按回车')
     * @ux-default '请输入标签'
     */
    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    /**
     * 设置禁用状态
     * @param bool $disabled 是否禁用
     * @return static
     * @ux-example TagInput::make()->disabled()
     * @ux-default true
     */
    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;
        return $this;
    }

    /**
     * 设置最大标签数量（0 表示不限制）
     * @param int $max 最大数量
     * @return static
     * @ux-example TagInput::make()->maxCount(5)
     * @ux-default 0
     */
    public function maxCount(int $max): static
    {
        $this->maxCount = $max;
        return $this;
    }

    /**
     * 设置 LiveAction（标签变化时触发）
     * @param string $action Action 名称
     * @return static
     * @ux-example TagInput::make()->action('updateTags')
     */
    public function action(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    /**
     * 启用清除按钮
     * @param bool $allow 是否允许清除
     * @return static
     * @ux-example TagInput::make()->allowClear()
     * @ux-default true
     */
    public function allowClear(bool $allow = true): static
    {
        $this->allowClear = $allow;
        return $this;
    }

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-tag-input');
        if ($this->disabled) {
            $el->class('ux-tag-input-disabled');
        }

        $el->data('tag-value', json_encode($this->value));

        if ($this->maxCount > 0) {
            $el->data('tag-max', (string)$this->maxCount);
        }
        if ($this->action) {
            $el->data('tag-action', $this->action);
        }

        // 标签容器
        $containerEl = Element::make('div')->class('ux-tag-input-container');

        // 已存在的标签
        foreach ($this->value as $tag) {
            $tagEl = Element::make('span')
                ->class('ux-tag-input-tag')
                ->text($tag);

            if (!$this->disabled) {
                $removeEl = Element::make('span')
                    ->class('ux-tag-input-tag-remove')
                    ->html('<i class="bi bi-x"></i>');
                $tagEl->child($removeEl);
            }

            $containerEl->child($tagEl);
        }

        // 输入框
        if (!$this->disabled && ($this->maxCount === 0 || count($this->value) < $this->maxCount)) {
            $inputEl = Element::make('input')
                ->attr('type', 'text')
                ->attr('placeholder', $this->placeholder)
                ->class('ux-tag-input-field');
            $containerEl->child($inputEl);
        }

        $el->child($containerEl);

        // 隐藏的真实输入（用于表单提交）
        $hiddenEl = Element::make('input')
            ->attr('type', 'hidden')
            ->attr('name', $this->id ?? '')
            ->attr('value', implode(',', $this->value))
            ->class('ux-tag-input-hidden');
        $el->child($hiddenEl);

        // Live 桥接隐藏 input
        $liveInput = $this->createLiveModelInput(implode(',', $this->value));
        if ($liveInput) {
            $el->child($liveInput);
        }

        return $el;
    }
}
