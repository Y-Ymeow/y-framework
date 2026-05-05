<?php

declare(strict_types=1);

namespace Framework\UX\Data;

use Framework\View\Base\Element;

/**
 * 批量操作菜单
 *
 * 用于表格等多选场景的批量操作菜单，支持自定义动作、选中计数、可见性控制。
 *
 * @ux-category Data
 * @ux-since 1.0.0
 * @ux-example BatchActionsMenu::make()->action('删除', 'batchDelete', 'danger')->action('导出', 'batchExport')
 * @ux-example BatchActionsMenu::make()->actions($actions)->selectedKeys($selected)->liveAction('handleBatch')
 * @ux-css batch-actions.css
 */
class BatchActionsMenu
{
    protected string $emptyText = '';
    protected array $actions = [];
    protected ?string $liveAction = null;
    protected ?string $liveEvent = null;
    protected array $selectedKeys = [];
    protected bool $visible = false;
    protected string $selectCountText = '';

    /**
     * 设置空状态提示文字
     * @param string $text 提示文字
     * @return static
     */
    public function emptyText(string $text): static
    {
        $this->emptyText = $text;
        return $this;
    }

    /**
     * 设置选中计数显示文字
     * @param string $text 提示文字（支持 {count} 占位符）
     * @return static
     */
    public function selectCountText(string $text): static
    {
        $this->selectCountText = $text;
        return $this;
    }

    /**
     * 添加一个批量操作项
     * @param string $label 显示文字
     * @param string $action LiveAction 名称
     * @param string $variant 变体：default/primary/danger 等
     * @param string|null $icon 图标类名（可省略 bi- 前缀）
     * @param string|null $confirm 确认提示文字
     * @return static
     * @ux-example BatchActionsMenu::make()->action('删除', 'batchDelete', 'danger')->action('导出', 'batchExport')
     */
    public function action(string $label, string $action, string $variant = 'default', ?string $icon = null, ?string $confirm = null): static
    {
        $this->actions[] = [
            'label' => $label,
            'action' => $action,
            'variant' => $variant,
            'icon' => $icon,
            'confirm' => $confirm,
        ];
        return $this;
    }

    /**
     * 批量添加操作项
     * @param array $actions 操作配置数组
     * @return static
     */
    public function actions(array $actions): static
    {
        foreach ($actions as $action) {
            $this->action(
                $action['label'],
                $action['action'],
                $action['variant'] ?? 'default',
                $action['icon'] ?? null,
                $action['confirm'] ?? null
            );
        }
        return $this;
    }

    /**
     * 设置 LiveAction
     * @param string $action LiveAction 名称
     * @param string $event 触发事件
     * @return static
     */
    public function liveAction(string $action, string $event = 'click'): static
    {
        $this->liveAction = $action;
        $this->liveEvent = $event;
        return $this;
    }

    /**
     * 设置已选中的行 key 列表
     * @param array $keys 行 key 数组
     * @return static
     */
    public function selectedKeys(array $keys): static
    {
        $this->selectedKeys = $keys;
        return $this;
    }

    /**
     * 设置是否可见
     * @param bool $visible 是否可见
     * @return static
     * @ux-default false
     */
    public function visible(bool $visible = true): static
    {
        $this->visible = $visible;
        return $this;
    }

    public function render(): Element
    {
        $wrapper = Element::make('div')->class('ux-batch-actions');

        if ($this->visible && !empty($this->selectedKeys)) {
            $wrapper->class('ux-batch-actions-active');
        } else {
            $wrapper->class('ux-batch-actions-inactive');
        }

        $left = Element::make('div')->class('ux-batch-actions-left');

        if ($this->visible && !empty($this->selectedKeys)) {
            $countText = str_replace('{count}', (string)count($this->selectedKeys), $this->selectCountText ?: t('ux.selected_count', ['count' => count($this->selectedKeys)]));
            $left->child(Element::make('span')->class('ux-batch-actions-count')->text($countText));

            $dropdown = Element::make('div')->class('ux-batch-actions-dropdown');

            $triggerBtn = Element::make('button')
                ->attr('type', 'button')
                ->class('ux-batch-actions-trigger')
                ->text(t('ux.batch_actions'));

            $triggerIcon = Element::make('i')->class('bi bi-chevron-down', 'ux-batch-actions-trigger-arrow');
            $triggerBtn->child($triggerIcon);
            $dropdown->child($triggerBtn);

            $menu = Element::make('div')->class('ux-batch-actions-menu');
            foreach ($this->actions as $action) {
                $item = Element::make('button')
                    ->attr('type', 'button')
                    ->class('ux-batch-actions-item', "ux-batch-actions-item-{$action['variant']}");

                if (!empty($action['icon'])) {
                    $iconClass = str_starts_with($action['icon'], 'bi-') ? $action['icon'] : 'bi-' . $action['icon'];
                    $item->child(Element::make('i')->class($iconClass, 'ux-batch-actions-item-icon'));
                }

                $item->child(Element::make('span')->class('ux-batch-actions-item-label')->text($action['label']));

                if ($this->liveAction) {
                    $item->liveAction($this->liveAction, $this->liveEvent ?? 'click');
                    $item->data('action-params', json_encode([
                        'batchAction' => $action['action'],
                        'selectedKeys' => $this->selectedKeys,
                        'confirm' => $action['confirm'] ?? null,
                    ], JSON_UNESCAPED_UNICODE));
                }

                if ($action['confirm']) {
                    $item->data('confirm', $action['confirm']);
                }

                $menu->child($item);
            }

            $dropdown->child($menu);
            $left->child($dropdown);
        } else {
            $left->child(Element::make('span')->class('ux-batch-actions-empty')->text($this->emptyText ?: t('ux.please_select_records')));
        }

        $wrapper->child($left);

        $cancelBtn = Element::make('button')
            ->attr('type', 'button')
            ->class('ux-batch-actions-cancel')
            ->text(t('ux.cancel_selection'));
        $cancelBtn->data('action', 'cancelSelection');
        $wrapper->child($cancelBtn);

        return $wrapper;
    }
}
