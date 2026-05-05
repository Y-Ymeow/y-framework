<?php

declare(strict_types=1);

namespace Admin\Services;

class ActivityLogger
{
    public static function log(
        string $module,
        string $action,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?array $properties = null
    ): void {
        $userId = null;
        if (auth()->check()) {
            $userId = auth()->id();
        }

        $request = request();
        $ip = $request ? $request->ip() : null;
        $userAgent = $request && $request->headers() ? ($request->headers()['User-Agent'] ?? null) : null;

        db()->table('activity_logs')->insert([
            'user_id' => $userId,
            'module' => $module,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'properties' => $properties ? json_encode($properties, JSON_UNESCAPED_UNICODE) : null,
            'ip' => $ip,
            'user_agent' => is_array($userAgent) ? $userAgent[0] ?? null : $userAgent,
        ]);
    }

    public static function logLogin(int $userId, bool $success = true): void
    {
        static::log('user', $success ? 'logged_in' : 'login_failed', 'user', $userId);
    }

    public static function logLogout(int $userId): void
    {
        static::log('user', 'logged_out', 'user', $userId);
    }

    public static function logCreated(string $module, int $id, ?array $data = null): void
    {
        static::log($module, 'created', $module, $id, $data);
    }

    public static function logUpdated(string $module, int $id, ?array $data = null): void
    {
        static::log($module, 'updated', $module, $id, $data);
    }

    public static function logDeleted(string $module, int $id, ?array $data = null): void
    {
        static::log($module, 'deleted', $module, $id, $data);
    }
}
