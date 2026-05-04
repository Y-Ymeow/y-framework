<?php

declare(strict_types=1);

namespace Framework\View\Concerns;

/**
 * 可见性控制 trait
 *
 * 提供元素可见性、隐藏、防闪烁和状态存储相关方法。
 *
 * @view-category 辅助功能
 * @view-since 1.0.0
 */
trait HasVisibility
{
    /**
     * 快捷设置元素可见性
     *
     * @view-since 1.0.0
     * @param bool $visible 是否可见
     * @view-default true
     * @return static
     */
    public function visible(bool $visible = true): static
    {
        if ($visible) {
            unset($this->attrs['style']);
            $this->attrs['data-visible'] = 'true';
        } else {
            $this->hidden();
        }
        return $this;
    }

    /**
     * 快捷设置元素隐藏
     *
     * @view-since 1.0.0
     * @return static
     */
    public function hidden(): static
    {
        $this->attrs['hidden'] = '';
        $this->attrs['data-visible'] = 'false';
        return $this;
    }

    /**
     * 隐藏未渲染内容（data-cloak）
     *
     * 在 JS 加载完成前隐藏元素，防止闪烁
     *
     * @view-since 1.0.0
     * @return static
     */
    public function cloak(): static
    {
        $this->attrs['data-cloak'] = '';
        return $this;
    }

    /**
     * 设置 data-state 状态数据（JSON 编码）
     *
     * 用于在元素上存储结构化状态信息
     *
     * @view-since 1.0.0
     * @param array $data 状态数据
     * @return static
     */
    public function state(array $data): static
    {
        $this->attrs['data-state'] = json_encode($data, JSON_UNESCAPED_UNICODE);
        return $this;
    }
}
