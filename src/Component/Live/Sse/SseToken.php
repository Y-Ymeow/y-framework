<?php

declare(strict_types=1);

namespace Framework\Component\Live\Sse;

use Framework\Http\Session;

/**
 * SSE 安全令牌
 *
 * ## 安全设计
 *
 * 1. Token 绑定 Session ID，防止跨用户使用
 * 2. 可绑定用户 ID，登录后自动关联
 * 3. 设置过期时间，默认 24 小时
 * 4. 可限制订阅频道，防止越权
 * 5. 签名验证，防止篡改
 *
 * @since 2.0
 */
class SseToken
{
    private string $id;
    private string $sessionId;
    private ?int $userId;
    private array $channels;
    private int $expiresAt;
    private string $signature;

    private static string $secret = '';
    private static int $defaultTtl = 86400; // 24 hours

    public function __construct(
        string $id,
        string $sessionId,
        ?int $userId = null,
        array $channels = [],
        int $expiresAt = 0,
        string $signature = ''
    ) {
        $this->id = $id;
        $this->sessionId = $sessionId;
        $this->userId = $userId;
        $this->channels = $channels;
        $this->expiresAt = $expiresAt > 0 ? $expiresAt : time() + self::$defaultTtl;
        $this->signature = $signature;
    }

    /**
     * 设置签名密钥（应用启动时调用）
     */
    public static function setSecret(string $secret): void
    {
        self::$secret = $secret;
    }

    /**
     * 设置默认过期时间
     */
    public static function setDefaultTtl(int $ttl): void
    {
        self::$defaultTtl = $ttl;
    }

    /**
     * 为当前会话生成 Token
     *
     * @param array $channels 允许订阅的频道（空数组表示允许所有）
     * @param int $ttl 过期时间（秒）
     */
    public static function generate(array $channels = [], int $ttl = 0): self
    {
        $session = new Session();
        $sessionId = $session->getId();

        $id = bin2hex(random_bytes(16));
        $userId = $session->get('user_id') ?? $session->get('auth.user_id');

        // 关闭 Session 释放锁（关键！）
        $session->close();

        $expiresAt = time() + ($ttl > 0 ? $ttl : self::$defaultTtl);

        $token = new self($id, $sessionId, $userId, $channels, $expiresAt);
        $token->signature = $token->computeSignature();

        return $token;
    }

    /**
     * 从字符串解析 Token
     *
     * @param string $tokenString Token 字符串（JSON 或 Base64）
     */
    public static function parse(string $tokenString): ?self
    {
        try {
            $data = json_decode(base64_decode($tokenString), true);
            if (!$data || !isset($data['id'], $data['session_id'], $data['expires_at'], $data['signature'])) {
                return null;
            }

            return new self(
                $data['id'],
                $data['session_id'],
                $data['user_id'] ?? null,
                $data['channels'] ?? [],
                $data['expires_at'],
                $data['signature']
            );
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * 验证 Token 是否有效
     *
     * @param bool $checkSession 是否验证 Session 匹配
     */
    public function isValid(bool $checkSession = true): bool
    {
        // 检查签名
        if (!$this->verifySignature()) {
            return false;
        }

        // 检查过期
        if ($this->expiresAt < time()) {
            return false;
        }

        // 检查 Session 匹配
        if ($checkSession) {
            $session = new Session();
            if ($session->getId() !== $this->sessionId) {
                return false;
            }
        }

        return true;
    }

    /**
     * 检查是否可以订阅指定频道
     */
    public function canSubscribe(string $channel): bool
    {
        // 如果没有限制频道，允许所有
        if (empty($this->channels)) {
            return true;
        }

        return in_array($channel, $this->channels, true);
    }

    /**
     * 检查是否可以接收指定用户的消息
     */
    public function canReceiveUserMessage(?int $targetUserId): bool
    {
        // 如果消息没有指定用户，允许接收
        if ($targetUserId === null) {
            return true;
        }

        // 如果 Token 没有绑定用户，只接收广播消息
        if ($this->userId === null) {
            return false;
        }

        // 只接收自己的消息
        return $this->userId === $targetUserId;
    }

    /**
     * 转换为字符串（用于传输）
     */
    public function toString(): string
    {
        return base64_encode(json_encode([
            'id' => $this->id,
            'session_id' => $this->sessionId,
            'user_id' => $this->userId,
            'channels' => $this->channels,
            'expires_at' => $this->expiresAt,
            'signature' => $this->signature,
        ], JSON_UNESCAPED_UNICODE));
    }

    /**
     * 计算签名
     */
    private function computeSignature(): string
    {
        $data = implode('|', [
            $this->id,
            $this->sessionId,
            $this->userId ?? '',
            implode(',', $this->channels),
            $this->expiresAt,
        ]);

        return hash_hmac('sha256', $data, self::$secret);
    }

    /**
     * 验证签名
     */
    private function verifySignature(): bool
    {
        return hash_equals($this->computeSignature(), $this->signature);
    }

    // Getters

    public function getId(): string
    {
        return $this->id;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function getChannels(): array
    {
        return $this->channels;
    }

    public function getExpiresAt(): int
    {
        return $this->expiresAt;
    }
}
