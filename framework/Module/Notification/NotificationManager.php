<?php

declare(strict_types=1);

namespace Framework\Module\Notification;

use Framework\Component\Live\LiveNotifier;

class NotificationManager
{
    private array $channels = [];

    public function channel(string $name, callable $handler): self
    {
        $this->channels[$name] = $handler;
        return $this;
    }

    public function send(int $userId, string $type, string $title, string $message, array $data = []): Notification
    {
        $notification = Notification::send($userId, $type, $title, $message, $data);

        LiveNotifier::toUser($userId, 'notifications', [
            'event' => 'notification.new',
            'id' => $notification->id,
            'type' => $type,
            'title' => $title,
        ]);

        foreach ($this->channels as $name => $handler) {
            try {
                $handler($notification);
            } catch (\Throwable $e) {
            }
        }

        return $notification;
    }

    public function sendNow(int $userId, string $type, string $title, string $message, array $data = []): Notification
    {
        return $this->send($userId, $type, $title, $message, $data);
    }

    public function unread(int $userId): array
    {
        return Notification::unread($userId);
    }

    public function unreadCount(int $userId): int
    {
        return Notification::unreadCount($userId);
    }

    public function markRead(int $id): void
    {
        Notification::markRead($id);
    }

    public function markAllRead(int $userId): void
    {
        Notification::markAllRead($userId);
    }
}
