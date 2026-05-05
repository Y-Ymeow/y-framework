<?php

namespace Admin\Components\Notifications;

use Framework\Component\Live\LiveComponent;
use Framework\Component\Live\Attribute\State;
use Framework\View\Base\Element;

class NotificationList extends LiveComponent
{
    #[State]
    public array $notifications = [];

    #[State]
    public bool $showAll = false;

    protected int $maxVisible = 5;

    public static function getName(): string
    {
        return 'notification-list';
    }

    public static function addNotification(string $type, string $message): void
    {
        $notification = [
            'id' => uniqid('notif_'),
            'type' => $type,
            'message' => $message,
            'time' => date('Y-m-d H:i:s'),
        ];

        $notifications = session()->get('admin_notifications', []);
        $notifications[] = $notification;
        session()->set('admin_notifications', $notifications);
    }

    public function mount(): void
    {
        $this->notifications = session()->get('admin_notifications', []);
        session()->remove('admin_notifications');
    }

    public function markAsRead(string $id): void
    {
        $this->notifications = array_filter(
            $this->notifications,
            fn($n) => $n['id'] !== $id
        );
    }

    public function markAllAsRead(): void
    {
        $this->notifications = [];
    }

    public function toggleShowAll(): void
    {
        $this->showAll = !$this->showAll;
    }

    public function render(): Element
    {
        $visible = $this->showAll
            ? $this->notifications
            : array_slice($this->notifications, 0, $this->maxVisible);

        $wrapper = Element::make('div')
            ->class('notification-list', 'space-y-2');

        if (empty($this->notifications)) {
            $empty = Element::make('div')
                ->class('text-center', 'py-8', 'text-gray-400')
                ->child(Element::make('i')->class('bi bi-bell-slash text-3xl mb-2'))
                ->child(Element::make('p')->intl('admin.no_notifications'));
            $wrapper->child($empty);
            return $wrapper;
        }

        $header = Element::make('div')
            ->class('flex', 'items-center', 'justify-between', 'mb-3');
        $header->child(Element::make('h3')
            ->class('font-semibold', 'text-gray-700')
            ->intl('admin.notifications') . ' (' . count($this->notifications) . ')');

        if (count($this->notifications) > $this->maxVisible) {
            $toggleBtn = Element::make('button')
                ->class('text-sm', 'text-blue-600', 'hover:underline')
                ->attr('type', 'button')
                ->liveAction('toggleShowAll')
                ->text($this->showAll ? t('admin.show_less') : t('admin.show_all'));
            $header->child($toggleBtn);
        }

        $markAllBtn = Element::make('button')
            ->class('text-sm', 'text-gray-500', 'hover:text-gray-700')
            ->attr('type', 'button')
            ->liveAction('markAllAsRead')
            ->intl('admin.mark_all_read');
        $header->child($markAllBtn);

        $wrapper->child($header);

        $list = Element::make('div')->class('space-y-2');
        foreach ($visible as $notification) {
            $item = $this->buildNotificationItem($notification);
            $list->child($item);
        }
        $wrapper->child($list);

        return $wrapper;
    }

    protected function buildNotificationItem(array $notification): Element
    {
        $typeClasses = [
            'success' => 'border-l-green-500',
            'error' => 'border-l-red-500',
            'warning' => 'border-l-yellow-500',
            'info' => 'border-l-blue-500',
        ];

        $item = Element::make('div')
            ->class('notification-item', 'flex', 'items-start', 'gap-3', 'p-3', 'bg-white', 'rounded-lg', 'border', 'border-l-4', 'shadow-sm')
            ->class($typeClasses[$notification['type']] ?? 'border-l-gray-300');

        $iconMap = [
            'success' => 'bi-check-circle text-green-500',
            'error' => 'bi-x-circle text-red-500',
            'warning' => 'bi-exclamation-triangle text-yellow-500',
            'info' => 'bi-info-circle text-blue-500',
        ];

        $icon = Element::make('i')
            ->class('bi', $iconMap[$notification['type']] ?? 'bi-info-circle text-gray-500', 'mt-0.5');
        $item->child($icon);

        $content = Element::make('div')->class('flex-1', 'min-w-0');
        $content->child(Element::make('p')
            ->class('text-sm', 'text-gray-700')
            ->text($notification['message']));
        $content->child(Element::make('p')
            ->class('text-xs', 'text-gray-400', 'mt-1')
            ->text($notification['time']));

        $item->child($content);

        $dismissBtn = Element::make('button')
            ->class('text-gray-400', 'hover:text-gray-600', 'p-1')
            ->attr('type', 'button')
            ->liveAction('markAsRead')
            ->data('id', $notification['id'])
            ->child(Element::make('i')->class('bi bi-x'));
        $item->child($dismissBtn);

        return $item;
    }
}
