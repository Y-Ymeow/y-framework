<?php

declare(strict_types=1);

namespace Framework\Http;

use Framework\Foundation\AppEnvironment;

/**
 * SseResponse 长连接 SSE 响应
 *
 * 服务器推送（Server-Sent Events）响应，支持 keep-alive 心跳、
 * 定时轮询回调、频道订阅、最大执行时间限制。
 *
 * ## 与 StreamResponse 的区别
 *
 * - `StreamResponse`：一次性 Generator 流，数据发完即结束
 * - `SseResponse`：长连接，通过 onInterval 持续推送，直到客户端断开或超时
 *
 * @http-category Response
 * @http-since 2.0
 *
 * @http-example
 * // 简单定时推送
 * return SseResponse::simple(function () {
 *     return ['event' => 'tick', 'data' => ['time' => date('H:i:s')]];
 * }, 1000);
 *
 * // 完整构建
 * return SseResponse::create()
 *     ->event('init', ['status' => 'connected'])
 *     ->keepAlive(30)
 *     ->onInterval(function () {
 *         $msg = SseHub::getMessages('notifications');
 *         return $msg ? ['event' => 'notification', 'data' => $msg] : null;
 *     }, 1000)
 *     ->maxExecTime(3600);
 * @http-example-end
 */
class SseResponse extends Response
{
    private array $events = [];
    private int $keepAlive = 0;
    private $onInterval = null;
    private int $intervalMs = 1000;
    private int $maxExecTime = 0;
    private array $channels = [];

    private function __construct()
    {
        $this->statusCode = 200;
        $this->statusText = 'OK';
        $this->headers = [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
            'Access-Control-Allow-Origin' => '*',
        ];
    }

    /**
     * 创建 SSE 响应实例
     * @return static
     * @http-example SseResponse::create()->event('init', ['status'=>'ok'])
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * 添加初始事件（连接建立时立即发送）
     * @param string $event 事件名称
     * @param mixed $data 事件数据
     * @param string|null $id 事件 ID
     * @return static
     */
    public function event(string $event, mixed $data, ?string $id = null): self
    {
        $this->events[] = [
            'event' => $event,
            'data' => $data,
            'id' => $id,
        ];
        return $this;
    }

    /**
     * 设置 keep-alive 心跳间隔
     * @param int $seconds 心跳间隔秒数
     * @return static
     */
    public function keepAlive(int $seconds): self
    {
        $this->keepAlive = $seconds;
        return $this;
    }

    /**
     * 设置定时轮询回调
     * @param callable $callback 回调函数，返回事件数组或 null
     * @param int $intervalMs 轮询间隔毫秒
     * @return static
     */
    public function onInterval(callable $callback, int $intervalMs = 1000): self
    {
        $this->onInterval = $callback;
        $this->intervalMs = $intervalMs;
        return $this;
    }

    /**
     * 设置最大执行时间
     * @param int $seconds 最大执行秒数，0 为无限
     * @return static
     */
    public function maxExecTime(int $seconds): self
    {
        $this->maxExecTime = $seconds;
        return $this;
    }

    /**
     * 订阅 SSE 频道
     * @param string ...$channels 频道名称列表
     * @return static
     */
    public function subscribe(string ...$channels): self
    {
        $this->channels = array_merge($this->channels, $channels);
        return $this;
    }

    /**
     * 发送 SSE 响应：发送初始事件 → 进入轮询循环
     * @return void
     */
    public function send(): void
    {
        if (AppEnvironment::isWasm()) {
            $events = $this->events;
            if ($this->onInterval) {
                $events[] = ['event' => 'info', 'data' => ['mode' => 'poll_fallback']];
            }
            echo json_encode(['sse' => $events], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->sendHeaders();

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        if (function_exists('apache_setenv')) {
            @apache_setenv('no-gzip', '1');
        }

        if ($this->maxExecTime === 0) {
            set_time_limit(0);
        } else {
            set_time_limit($this->maxExecTime);
        }

        foreach ($this->events as $event) {
            $this->sendEvent($event['event'], $event['data'], $event['id']);
        }

        if (!$this->onInterval) {
            return;
        }

        $lastKeepAlive = time();
        $startTime = time();

        while (true) {
            if ($this->maxExecTime > 0 && (time() - $startTime) >= $this->maxExecTime) {
                $this->sendEvent('close', ['reason' => 'timeout']);
                break;
            }

            if (connection_aborted()) {
                break;
            }

            $result = call_user_func($this->onInterval);

            if ($result !== null) {
                if (isset($result['event'])) {
                    $this->sendEvent(
                        $result['event'],
                        $result['data'] ?? $result,
                        $result['id'] ?? null
                    );
                } elseif (is_array($result)) {
                    foreach ($result as $event) {
                        if (isset($event['event'])) {
                            $this->sendEvent(
                                $event['event'],
                                $event['data'] ?? $event,
                                $event['id'] ?? null
                            );
                        }
                    }
                }
            }

            if ($this->keepAlive > 0 && (time() - $lastKeepAlive) >= $this->keepAlive) {
                $this->sendEvent('ping', ['time' => time()]);
                $lastKeepAlive = time();
            }

            usleep($this->intervalMs * 1000);
        }
    }

    /**
     * 收集初始事件为字符串
     * @return string
     */
    public function getContent(): string
    {
        $output = '';
        foreach ($this->events as $event) {
            $output .= $this->formatEvent($event['event'], $event['data'], $event['id']);
        }
        return $output;
    }

    /**
     * 快速创建定时推送 SSE
     * @param callable $callback 回调函数
     * @param int $intervalMs 轮询间隔毫秒
     * @return static
     * @http-example SseResponse::simple(fn() => ['event'=>'tick','data'=>['ts'=>time()]], 1000)
     */
    public static function simple(callable $callback, int $intervalMs = 1000): self
    {
        return self::create()->onInterval($callback, $intervalMs);
    }

    /** @internal */
    private function sendEvent(string $event, mixed $data, ?string $id = null): void
    {
        echo $this->formatEvent($event, $data, $id);

        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }

    /** @internal */
    private function formatEvent(string $event, mixed $data, ?string $id = null): string
    {
        $output = '';

        if ($id !== null) {
            $output .= "id: {$id}\n";
        }

        $output .= "event: {$event}\n";

        if (is_string($data)) {
            $output .= "data: {$data}\n";
        } else {
            $output .= "data: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
        }

        $output .= "\n";
        return $output;
    }
}
