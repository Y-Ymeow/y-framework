<?php

declare(strict_types=1);

namespace Framework\Component\Live\Sse;

/**
 * SSE Hub — 中心化推送服务
 *
 * ## 架构设计
 *
 * ```
 * ┌─────────────────────────────────────────────────────────────┐
 * │                        SSE Hub                              │
 * │                                                             │
 * │  ┌─────────┐    ┌─────────────┐    ┌─────────────────┐    │
 * │  │ push()  │───▶│  Message    │───▶│  Cache/Store    │    │
 * │  │         │    │  Queue      │    │                 │    │
 * │  └─────────┘    └─────────────┘    └────────┬────────┘    │
 * │                                              │              │
 * │  ┌─────────────────────────────────────────▼──────────┐   │
 * │  │              SseEndpoint (/__sse__/{token})         │   │
 * │  │  • 验证 Token                                        │   │
 * │  │  • 订阅频道                                          │   │
 * │  │  • 轮询消息队列                                      │   │
 * │  │  • 推送 SSE 事件                                     │   │
 * │  └─────────────────────────────────────────────────────┘   │
 * └─────────────────────────────────────────────────────────────┘
 * ```
 *
 * ## 消息类型
 *
 * 1. **广播消息**: 推送到所有订阅者
 * 2. **频道消息**: 推送到指定频道
 * 3. **用户消息**: 推送到指定用户（需登录）
 * 4. **LiveAction**: 触发前端组件更新
 *
 * @since 2.0
 *
 * @example
 * // 推送通知
 * SseHub::push('notifications', ['message' => '新消息']);
 *
 * // 触发 LiveAction 更新
 * SseHub::liveAction('user-dashboard', 'refreshData', ['limit' => 10]);
 *
 * // 推送给指定用户
 * SseHub::toUser(123, 'private', ['data' => '...']);
 */
class SseHub
{
    private static ?SseHub $instance = null;
    private static string $cacheDir = '';
    private static int $messageTtl = 3600;
    private static int $gcProbability = 5;
    private static int $gcDivisor = 100;
    private static int $lastCleanup = 0;

    private function __construct()
    {
    }

    /**
     * 获取单例实例
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
            self::initCache();
        }
        return self::$instance;
    }

    /**
     * 设置缓存目录
     */
    public static function setCacheDir(string $dir): void
    {
        self::$cacheDir = $dir;
    }

    /**
     * 设置消息过期时间
     */
    public static function setMessageTtl(int $ttl): void
    {
        self::$messageTtl = $ttl;
    }

    /**
     * 推送消息到频道
     *
     * @param string $channel 频道名称
     * @param array $message 消息内容
     * @param string $event SSE 事件名称（默认 'message'）
     */
    public static function push(string $channel, array $message, string $event = 'message'): void
    {
        self::getInstance()->storeMessage([
            'channel' => $channel,
            'event' => $event,
            'data' => $message,
            'timestamp' => microtime(true),
        ]);
    }

    /**
     * 推送消息给指定用户
     *
     * @param int $userId 用户 ID
     * @param string $channel 频道名称
     * @param array $message 消息内容
     */
    public static function toUser(int $userId, string $channel, array $message): void
    {
        self::getInstance()->storeMessage([
            'channel' => $channel,
            'user_id' => $userId,
            'event' => 'message',
            'data' => $message,
            'timestamp' => microtime(true),
        ]);
    }

    /**
     * 触发 LiveComponent 的 Action 更新
     *
     * @param string $componentId 组件 ID（data-live-id 的值）
     * @param string $action Action 方法名
     * @param array $params 传递给 Action 的参数
     * @param string|null $channel 频道（可选，用于过滤接收者）
     */
    public static function liveAction(
        string $componentId,
        string $action,
        array $params = [],
        ?string $channel = null
    ): void {
        self::getInstance()->storeMessage([
            'channel' => $channel ?? 'live',
            'event' => 'live:action',
            'data' => [
                'componentId' => $componentId,
                'action' => $action,
                'params' => $params,
            ],
            'timestamp' => microtime(true),
        ]);
    }

    /**
     * 批量更新多个组件
     *
     * @param array $updates 更新列表 [['componentId' => '...', 'action' => '...', 'params' => []], ...]
     * @param string|null $channel 频道
     */
    public static function liveBatch(array $updates, ?string $channel = null): void
    {
        foreach ($updates as $update) {
            self::liveAction(
                $update['componentId'] ?? '',
                $update['action'] ?? '',
                $update['params'] ?? [],
                $channel
            );
        }
    }

    /**
     * 推送状态更新（直接更新组件属性）
     *
     * @param string $componentId 组件 ID
     * @param array $state 状态数据
     * @param string|null $channel 频道
     */
    public static function liveState(string $componentId, array $state, ?string $channel = null): void
    {
        self::getInstance()->storeMessage([
            'channel' => $channel ?? 'live',
            'event' => 'live:state',
            'data' => [
                'componentId' => $componentId,
                'state' => $state,
            ],
            'timestamp' => microtime(true),
        ]);
    }

    /**
     * 获取频道的消息
     *
     * @param string $channel 频道名称
     * @param float $since 获取此时间戳之后的消息
     * @param int|null $userId 用户 ID（用于过滤私有消息）
     * @return array 消息列表
     */
    public function getMessages(string $channel, float $since = 0, ?int $userId = null): array
    {
        $this->maybeCleanup();
        $messages = [];
        $file = $this->getChannelFile($channel);

        if (!file_exists($file)) {
            return [];
        }

        $content = file_get_contents($file);
        if ($content === false) {
            return [];
        }

        $lines = array_filter(explode("\n", $content));

        foreach ($lines as $line) {
            $msg = json_decode($line, true);
            if (!$msg) continue;

            // 过滤时间
            if ($msg['timestamp'] <= $since) continue;

            // 过滤过期消息
            if ($msg['timestamp'] < time() - self::$messageTtl) continue;

            // 过滤用户
            if (isset($msg['user_id']) && $msg['user_id'] !== $userId) continue;

            $messages[] = $msg;
        }

        return $messages;
    }

    /**
     * 获取多个频道的消息
     */
    public function getMessagesForChannels(array $channels, float $since = 0, ?int $userId = null): array
    {
        $allMessages = [];

        foreach ($channels as $channel) {
            $messages = $this->getMessages($channel, $since, $userId);
            $allMessages = array_merge($allMessages, $messages);
        }

        // 按时间排序
        usort($allMessages, fn($a, $b) => $a['timestamp'] <=> $b['timestamp']);

        return $allMessages;
    }

    /**
     * 清理过期消息
     */
    public function cleanup(): void
    {
        $files = glob(self::$cacheDir . '/sse_*.jsonl');
        if ($files === false) return;

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) continue;

            $lines = explode("\n", $content);
            $validLines = [];

            foreach ($lines as $line) {
                if (!$line) continue;
                $msg = json_decode($line, true);
                if (!$msg) continue;

                // 保留未过期的消息
                if ($msg['timestamp'] >= time() - self::$messageTtl) {
                    $validLines[] = $line;
                }
            }

            if (empty($validLines)) {
                @unlink($file);
            } else {
                file_put_contents($file, implode("\n", $validLines) . "\n");
            }
        }

        self::$lastCleanup = time();
    }

    /**
     * 按概率自动清理
     * 同时确保同一进程内不会频繁清理
     */
    private function maybeCleanup(): void
    {
        $now = time();
        if ($now - self::$lastCleanup < 60) {
            return;
        }

        if (mt_rand(1, self::$gcDivisor) > self::$gcProbability) {
            return;
        }

        $this->cleanup();
    }

    /**
     * 静态清理方法（可供外部调用）
     */
    public static function gc(): void
    {
        self::getInstance()->cleanup();
    }

    /**
     * 设置清理概率
     *
     * @param int $probability 概率分子（默认 5）
     * @param int $divisor 概率分母（默认 100）
     */
    public static function setGcProbability(int $probability, int $divisor = 100): void
    {
        self::$gcProbability = $probability;
        self::$gcDivisor = $divisor;
    }

    /**
     * 存储消息
     */
    private function storeMessage(array $message): void
    {
        $file = $this->getChannelFile($message['channel']);
        $line = json_encode($message, JSON_UNESCAPED_UNICODE) . "\n";

        // 确保目录存在
        $dir = dirname($file);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    /**
     * 获取频道文件路径
     */
    private function getChannelFile(string $channel): string
    {
        $safeChannel = preg_replace('/[^a-zA-Z0-9_-]/', '_', $channel);
        return self::$cacheDir . '/sse_' . $safeChannel . '.jsonl';
    }

    /**
     * 初始化缓存目录
     */
    private static function initCache(): void
    {
        if (self::$cacheDir === '') {
            // 使用项目的 storage 目录，确保跨进程共享
            self::$cacheDir = base_path('storage/sse');
        }

        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0755, true);
        }
    }
}
