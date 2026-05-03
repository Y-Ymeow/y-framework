<?php

declare(strict_types=1);

namespace Framework\Component\Live;

use Framework\Component\Live\Sse\SseHub;

/**
 * LiveNotifier 实时通知器
 *
 * 从 Live 组件外部推送更新到前端组件。
 * 支持两种推送方式：
 *
 * ## 1. SSE 推送（实时，需要前端 SSE 连接）
 *
 * 前端通过 `data-live-sse` 属性订阅频道，后端通过 SseHub 推送消息。
 * 适用于：实时通知、聊天、协作编辑等场景。
 *
 * ## 2. Poll 轮询（兼容性好，不需要 SSE 连接）
 *
 * 前端通过 `data-poll` 属性定时轮询，后端通过 LiveEventBus 触发更新。
 * 适用于：进度条、状态监控等不需要即时性的场景。
 *
 * @live-category Notification
 * @live-since 2.0
 *
 * @example
 * // SSE 推送：触发组件 Action
 * LiveNotifier::action('user-dashboard', 'refreshData', ['limit' => 10]);
 *
 * // SSE 推送：直接更新组件属性
 * LiveNotifier::state('notification-badge', ['count' => 5]);
 *
 * // SSE 推送：广播消息
 * LiveNotifier::broadcast('orders', ['event' => 'new_order', 'id' => 123]);
 *
 * // SSE 推送：推送给指定用户
 * LiveNotifier::toUser(1, 'notifications', ['message' => '你有新消息']);
 *
 * // 同步事件：在同一请求周期内触发组件更新
 * LiveNotifier::emit('order.created', ['orderId' => 123]);
 */
class LiveNotifier
{
    /**
     * 通过 SSE 触发组件 Action
     *
     * 前端收到 `live:action` 事件后，自动调用 `POST /live/update` 执行指定 Action。
     *
     * @param string $componentId 组件 ID（data-live-id 的值）
     * @param string $action Action 方法名
     * @param array $params 传递给 Action 的参数
     * @param string|null $channel SSE 频道，默认 'live'
     *
     * @live-example LiveNotifier::action('user-dashboard', 'refreshData', ['limit' => 10])
     */
    public static function action(
        string $componentId,
        string $action,
        array $params = [],
        ?string $channel = null
    ): void {
        SseHub::liveAction($componentId, $action, $params, $channel);
    }

    /**
     * 通过 SSE 直接更新组件属性
     *
     * 前端收到 `live:state` 事件后，直接合并 state 到组件，不触发 Action。
     *
     * @param string $componentId 组件 ID
     * @param array $state 要更新的属性键值对
     * @param string|null $channel SSE 频道
     *
     * @live-example LiveNotifier::state('notification-badge', ['count' => 5, 'label' => '5条新消息'])
     */
    public static function state(string $componentId, array $state, ?string $channel = null): void
    {
        SseHub::liveState($componentId, $state, $channel);
    }

    /**
     * 通过 SSE 批量更新多个组件
     *
     * @param array $updates 更新列表
     * @param string|null $channel SSE 频道
     *
     * @live-example
     * LiveNotifier::batch([
     *     ['componentId' => 'badge', 'action' => 'refresh'],
     *     ['componentId' => 'list', 'action' => 'reload', 'params' => ['page' => 1]],
     * ])
     */
    public static function batch(array $updates, ?string $channel = null): void
    {
        SseHub::liveBatch($updates, $channel);
    }

    /**
     * 通过 SSE 广播消息到频道
     *
     * @param string $channel 频道名称
     * @param array $message 消息内容
     * @param string $event SSE 事件名称
     *
     * @live-example LiveNotifier::broadcast('orders', ['event' => 'new_order', 'id' => 123])
     */
    public static function broadcast(string $channel, array $message, string $event = 'message'): void
    {
        SseHub::push($channel, $message, $event);
    }

    /**
     * 通过 SSE 推送消息给指定用户
     *
     * @param int $userId 用户 ID
     * @param string $channel 频道名称
     * @param array $message 消息内容
     *
     * @live-example LiveNotifier::toUser(1, 'notifications', ['message' => '你有新消息'])
     */
    public static function toUser(int $userId, string $channel, array $message): void
    {
        SseHub::toUser($userId, $channel, $message);
    }

    /**
     * 在当前请求周期内触发 LiveEventBus 事件
     *
     * 与 SSE 不同，这是同步的，只在当前请求的 LiveComponent 处理过程中生效。
     * 适用于同一请求内组件间通信。
     *
     * @param string $event 事件名称
     * @param mixed $data 事件数据
     *
     * @live-example LiveNotifier::emit('order.created', ['orderId' => 123])
     */
    public static function emit(string $event, mixed $data = null): void
    {
        LiveEventBus::recordEmittedEvent($event, $data);
    }
}
