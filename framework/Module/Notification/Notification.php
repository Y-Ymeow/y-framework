<?php

declare(strict_types=1);

namespace Framework\Module\Notification;

use Framework\Database\Model;

class Notification extends Model
{
    protected string $table = 'notifications';
    protected array $fillable = ['user_id', 'type', 'title', 'message', 'data', 'read_at'];
    protected array $casts = [
        'data' => 'json',
        'read_at' => 'datetime',
    ];

    public static function unread(int $userId): array
    {
        return static::where('user_id', $userId)
            ->whereNull('read_at')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public static function unreadCount(int $userId): int
    {
        return static::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();
    }

    public static function markRead(int $id): void
    {
        $notification = static::find($id);
        if ($notification) {
            $notification->read_at = date('Y-m-d H:i:s');
            $notification->save();
        }
    }

    public static function markAllRead(int $userId): void
    {
        $notifications = static::where('user_id', $userId)
            ->whereNull('read_at')
            ->get();

        foreach ($notifications as $notification) {
            $notification->read_at = date('Y-m-d H:i:s');
            $notification->save();
        }
    }

    public static function send(int $userId, string $type, string $title, string $message, array $data = []): self
    {
        return static::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data' => $data,
        ]);
    }

    public static function broadcast(string $type, string $title, string $message, array $data = []): void
    {
        $users = \Framework\Module\User\User::all();
        foreach ($users as $user) {
            static::send((int)$user['id'], $type, $title, $message, $data);
        }
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }
}
