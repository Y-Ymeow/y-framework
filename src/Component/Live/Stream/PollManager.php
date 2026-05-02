<?php

declare(strict_types=1);

namespace Framework\Component\Live\Stream;

use Framework\Component\Live\Attribute\LiveAction;

/**
 * Poll 轮询机制 — 定时请求更新
 *
 * ## 工作原理
 *
 * 最简单的实时更新方式：
 * 1. 前端设置定时器（setInterval）
 * 2. 定期调用后端 LiveAction
 * 3. 后端返回最新状态
 * 4. 前端更新 DOM
 *
 * ## 使用场景
 *
 * - 状态检查（任务进度、订单状态）
 * - 数据刷新（排行榜、统计数据）
 * - 简单通知
 * - 兼容性要求高的场景
 *
 * ## 与 SSE/Stream 对比
 *
 * | 特性 | Poll | SSE | Stream |
 * |------|------|-----|--------|
 * | 连接数 | 多次短连接 | 单长连接 | 单流连接 |
 * | 服务器压力 | 较高 | 低 | 低 |
 * | 实时性 | 有延迟 | 即时 | 即时 |
 * | 兼容性 | ✅ 最好 | ✅ 好 | ✅ 好 |
 * | 实现复杂度 | 最简单 | 中等 | 中等 |
 *
 * @since 2.0
 *
 * @example
 * // 在 LiveComponent 中使用
 * class TaskMonitor extends LiveComponent
 * {
 *     #[LivePoll(interval: 2000)] // 每 2 秒轮询
 *     public function checkStatus(): array
 *     {
 *         return [
 *             'progress' => $this->task->progress,
 *             'status' => $this->task->status,
 *         ];
 *     }
 * }
 *
 * // 前端自动处理（通过 data-poll 属性）
 * <div data-poll="checkStatus" data-poll-interval="2000">
 *     进度: {{ progress }}%
 * </div>
 */
class PollManager
{
    private static array $registry = [];

    /**
     * 注册可轮询的 Action
     *
     * @param string $componentId 组件 ID
     * @param string $action Action 名称
     * @param int $interval 轮询间隔（毫秒）
     * @param array $params 额外参数
     */
    public static function register(string $componentId, string $action, int $interval = 5000, array $params = []): void
    {
        self::$registry[$componentId][$action] = [
            'interval' => $interval,
            'params' => $params,
        ];
    }

    /**
     * 获取组件的轮询配置
     */
    public static function get(string $componentId): array
    {
        return self::$registry[$componentId] ?? [];
    }

    /**
     * 生成前端轮询配置（JSON）
     */
    public static function toJson(string $componentId): string
    {
        return json_encode(self::get($componentId), JSON_UNESCAPED_UNICODE);
    }

    /**
     * 清除组件的轮询配置
     */
    public static function clear(string $componentId): void
    {
        unset(self::$registry[$componentId]);
    }
}
