<?php

declare(strict_types=1);

namespace Framework\Component\Live;

use Framework\Component\Live\Attribute as Live;
use Framework\View\Base\Element;

/**
 * 确认对话框组件
 *
 * 用法：
 * ```php
 * // 组件内
 * echo live('confirm-dialog', id: 'delete-confirm');
 *
 * // 触发确认
 * button('删除')->confirm('delete-confirm', action: 'destroy', data: ['id' => 1]);
 * ```
 */
class ConfirmDialog extends LiveComponent
{
    public bool $visible = false;
    public string $title = '确认';
    public string $message = '确定要执行此操作吗？';
    public string $okText = '确定';
    public string $cancelText = '取消';
    public string $okVariant = 'primary';
    public string $cancelVariant = 'secondary';
    public string $targetAction = '';
    public array $targetData = [];
    public string $targetComponent = '';

    #[Live\LiveAction]
    public function show(array $params = []): void
    {
        $this->visible = true;
        $this->title = $params['title'] ?? $this->title;
        $this->message = $params['message'] ?? $this->message;
        $this->okText = $params['okText'] ?? $this->okText;
        $this->cancelText = $params['cancelText'] ?? $this->cancelText;
        $this->targetAction = $params['action'] ?? '';
        $this->targetData = $params['data'] ?? [];
        $this->targetComponent = $params['component'] ?? '';
    }

    #[Live\LiveAction]
    public function hide(): void
    {
        $this->visible = false;
    }

    #[Live\LiveAction]
    public function accept(): array
    {
        $this->visible = false;

        if ($this->targetAction && $this->targetComponent) {
            return [
                'emit' => [
                    'event' => 'confirm:accepted',
                    'data' => [
                        'action' => $this->targetAction,
                        'data' => $this->targetData,
                        'component' => $this->targetComponent,
                    ],
                ],
            ];
        }

        return [];
    }

    public function render()
    {
        if (!$this->visible) {
            return '';
        }

        return Element::make('div')
            ->attr('class', 'live-confirm-overlay')
            ->attr('data-live-ignore', '')
            ->children([
                Element::make('div')->attr('class', 'live-confirm-backdrop')
                    ->attr('wire:click', 'hide'),
                Element::make('div')->attr('class', 'live-confirm-dialog')
                    ->children([
                        Element::make('h3')->attr('class', 'live-confirm-title')
                            ->text($this->title),
                        Element::make('p')->attr('class', 'live-confirm-message')
                            ->text($this->message),
                        Element::make('div')->attr('class', 'live-confirm-actions')
                            ->children([
                                Element::make('button')
                                    ->attr('class', "live-confirm-btn live-confirm-cancel live-btn-{$this->cancelVariant}")
                                    ->attr('wire:click', 'hide')
                                    ->text($this->cancelText),
                                Element::make('button')
                                    ->attr('class', "live-confirm-btn live-confirm-ok live-btn-{$this->okVariant}")
                                    ->attr('wire:click', 'accept')
                                    ->text($this->okText),
                            ]),
                    ]),
            ]);
    }
}
