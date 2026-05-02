<?php

declare(strict_types=1);

namespace Framework\Component\Live\Sse;

use Framework\Foundation\AppEnvironment;
use Framework\Http\Session;
use Framework\Routing\Attribute\Route;

/**
 * SSE Endpoint — 统一 SSE 入口
 *
 * ## 工作流程
 *
 * 1. 前端通过 `data-live-sse` 属性声明需要订阅的频道
 * 2. 框架自动生成 Token 并注入到页面
 * 3. 前端连接 `/live/sse/{token}`
 * 4. Endpoint 验证 Token 并订阅频道
 * 5. 轮询 SseHub 获取消息并推送
 * 6. 收到 `live:action` 事件时，前端自动调用 LiveComponent 方法
 *
 * @since 2.0
 */
class SseEndpoint
{
    private ?SseToken $token = null;
    private ?SseHub $hub = null;
    private float $lastTimestamp = 0;
    private int $keepAlive = 30;
    private int $maxExecTime = 0;

    public function __construct()
    {
        // 无参构造函数，避免容器注入问题
    }

    private function setToken(SseToken $token): void
    {
        $this->token = $token;
        $this->hub = SseHub::getInstance();
    }

    /**
     * SSE 路由入口
     *
     * 路由: GET /live/sse/{token}
     */
    #[Route('/live/sse/{token}', ['GET'], name: 'live.sse')]
    public function __invoke(string $token): mixed
    {
        $endpoint = self::fromToken($token);
        if (!$endpoint) {
            return \Framework\Http\Response::json(['error' => 'Invalid or expired token'], 403);
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');
        http_response_code(200);

        $endpoint->handle();
        exit;
    }

    /**
     * 从请求创建 Endpoint
     *
     * @param string $tokenString Token 字符串
     */
    public static function fromToken(string $tokenString): ?self
    {
        $token = SseToken::parse($tokenString);
        if (!$token) {
            return null;
        }

        // SSE 连接不检查 Session（因为是无状态长连接）
        // 只验证签名和过期时间
        if (!$token->isValid(false)) {
            return null;
        }

        $endpoint = new self();
        $endpoint->setToken($token);
        return $endpoint;
    }

    /**
     * 设置心跳间隔
     */
    public function keepAlive(int $seconds): self
    {
        $this->keepAlive = $seconds;
        return $this;
    }

    /**
     * 设置最大执行时间
     */
    public function maxExecTime(int $seconds): self
    {
        $this->maxExecTime = $seconds;
        return $this;
    }

    /**
     * 处理 SSE 请求
     */
    public function handle(): void
    {
        if (!AppEnvironment::supportsHeaders()) {
            $this->handleWasm();
            return;
        }

        set_time_limit(0);

        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        $this->sendConnected();

        $lastKeepAlive = time();
        $startTime = time();

        $userId = $this->token->getUserId();

        $channels = $this->token->getChannels();
        if (empty($channels)) {
            $channels = ['default', 'live'];
        }

        while (true) {
            if ($this->maxExecTime > 0 && (time() - $startTime) >= $this->maxExecTime) {
                $this->sendEvent('close', ['reason' => 'timeout']);
                break;
            }

            if (connection_aborted()) {
                break;
            }

            $messages = $this->hub->getMessagesForChannels($channels, $this->lastTimestamp, $userId);

            foreach ($messages as $msg) {
                if (!$this->token->canSubscribe($msg['channel'])) {
                    continue;
                }

                if (isset($msg['user_id']) && !$this->token->canReceiveUserMessage($msg['user_id'])) {
                    continue;
                }

                $this->sendEvent($msg['event'], $msg['data']);
                $this->lastTimestamp = $msg['timestamp'];
            }

            if ($this->keepAlive > 0 && (time() - $lastKeepAlive) >= $this->keepAlive) {
                $this->sendEvent('ping', ['time' => time()]);
                $lastKeepAlive = time();
            }

            usleep(500000);
        }
    }

    /**
     * WASM 环境处理
     */
    private function handleWasm(): void
    {
        $userId = $this->token->getUserId();
        $channels = $this->token->getChannels() ?: ['default', 'live'];

        $messages = $this->hub->getMessagesForChannels($channels, 0, $userId);

        echo json_encode([
            'sse' => array_map(fn($msg) => [
                'event' => $msg['event'],
                'data' => $msg['data'],
            ], $messages),
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * 发送连接成功事件
     */
    private function sendConnected(): void
    {
        $this->sendEvent('connected', [
            'token_id' => $this->token->getId(),
            'channels' => $this->token->getChannels(),
            'user_id' => $this->token->getUserId(),
        ]);
    }

    /**
     * 发送 SSE 事件
     */
    private function sendEvent(string $event, mixed $data): void
    {
        echo "event: {$event}\n";
        echo "data: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";

        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }

    /**
     * 生成前端初始化脚本
     *
     * 返回包含 Token 和配置的 JSON，用于前端自动订阅
     */
    public static function generateClientConfig(): string
    {
        $session = new Session();
        $channels = $session->get('sse_channels', []);

        // 关闭 Session 释放锁
        $session->close();

        $token = SseToken::generate($channels);

        return json_encode([
            'token' => $token->toString(),
            'endpoint' => '/live/sse/' . $token->toString(),
            'channels' => $channels,
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * 注册路由（在应用启动时调用）
     */
    public static function registerRoute(): void
    {
        // 这个方法由路由系统调用，注册 /live/sse/{token} 路由
    }
}
