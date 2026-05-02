<?php

declare(strict_types=1);

namespace Framework\Component\Live\Attribute;

use Attribute;

/**
 * LivePoll — 标记可轮询的 LiveAction
 *
 * 将方法标记为可被前端定时轮询的 Action。
 * 前端会自动设置定时器，定期调用该方法获取更新。
 *
 * ## 参数
 *
 * - `interval`: 轮询间隔（毫秒），默认 5000（5秒）
 * - `immediate`: 是否立即执行一次，默认 true
 * - `condition`: 轮询条件表达式（JS），返回 false 时停止轮询
 *
 * @since 2.0
 *
 * @example
 * // 基础用法
 * #[LivePoll(interval: 3000)]
 * public function checkProgress(): array
 * {
 *     return ['progress' => $this->task->progress];
 * }
 *
 * // 带条件的轮询
 * #[LivePoll(interval: 1000, condition: 'status !== "completed"')]
 * public function syncStatus(): array
 * {
 *     return [
 *         'status' => $this->task->status,
 *         'progress' => $this->task->progress,
 *     ];
 * }
 *
 * // 延迟启动
 * #[LivePoll(interval: 5000, immediate: false)]
 * public function refreshData(): array
 * {
 *     return $this->repository->getLatest();
 * }
 */
#[Attribute(Attribute::TARGET_METHOD)]
class LivePoll
{
    public function __construct(
        public int $interval = 5000,
        public bool $immediate = true,
        public string $condition = ''
    ) {
    }
}
