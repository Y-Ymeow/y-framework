<?php

declare(strict_types=1);

namespace Framework\Component\Live\Attribute;

use Attribute;

/**
 * LiveSse — 标记返回 SSE 长连接响应的 LiveAction
 *
 * 将方法标记为 SSE Action，返回 SseResponse 对象。
 * 适用于需要服务器持续推送数据的场景（实时通知、状态监控等）。
 *
 * ## 参数
 *
 * - `keepAlive`: 心跳间隔秒数，默认 30
 * - `channels`: 订阅的 SSE 频道列表
 *
 * @live-category Attribute
 * @live-since 2.0
 *
 * @example
 * #[LiveSse(keepAlive: 30, channels: ['notifications'])]
 * public function notificationStream(): SseResponse
 * {
 *     return SseResponse::create()
 *         ->event('init', ['status' => 'connected'])
 *         ->keepAlive(30)
 *         ->onInterval(function () {
 *             $msgs = SseHub::getMessages('notifications', $since);
 *             return $msgs ? ['event' => 'notification', 'data' => $msgs] : null;
 *         }, 1000);
 * }
 */
#[Attribute(Attribute::TARGET_METHOD)]
class LiveSse
{
    public function __construct(
        public int $keepAlive = 30,
        public array $channels = []
    ) {
    }
}
