<?php

namespace Admin\Components\Notifications;

use Framework\Component\Live\LiveComponent;
use Framework\Component\Live\Attribute\Prop;
use Framework\View\Base\Element;

class ToastNotification extends LiveComponent
{
    #[Prop]
    public string $type = 'success';

    #[Prop]
    public string $message = '';

    #[Prop]
    public bool $dismissible = true;

    public static function getName(): string
    {
        return 'toast-notification';
    }

    public function render(): Element
    {
        $iconMap = [
            'success' => 'bi-check-circle-fill text-green-500',
            'error' => 'bi-x-circle-fill text-red-500',
            'warning' => 'bi-exclamation-triangle-fill text-yellow-500',
            'info' => 'bi-info-circle-fill text-blue-500',
        ];

        $bgMap = [
            'success' => 'bg-green-50 border-green-200',
            'error' => 'bg-red-50 border-red-200',
            'warning' => 'bg-yellow-50 border-yellow-200',
            'info' => 'bg-blue-50 border-blue-200',
        ];

        $toast = Element::make('div')
            ->class('toast-notification', 'flex', 'items-center', 'gap-3', 'p-4', 'rounded-lg', 'border', 'shadow-sm', 'animate-slide-in')
            ->class($bgMap[$this->type] ?? 'bg-gray-50 border-gray-200');

        $icon = Element::make('i')
            ->class('bi', $iconMap[$this->type] ?? 'bi-info-circle-fill text-gray-500', 'text-xl');
        $toast->child($icon);

        $content = Element::make('div')
            ->class('flex-1', 'text-sm', 'text-gray-700')
            ->text($this->message);
        $toast->child($content);

        if ($this->dismissible) {
            $closeBtn = Element::make('button')
                ->class('text-gray-400', 'hover:text-gray-600', 'transition-colors')
                ->attr('type', 'button')
                ->attr('data-dismiss', '')
                ->child(Element::make('i')->class('bi bi-x text-lg'));
            $toast->child($closeBtn);
        }

        return $toast;
    }
}
