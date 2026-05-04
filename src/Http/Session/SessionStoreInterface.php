<?php

declare(strict_types=1);

namespace Framework\Http\Session;

/**
 * Session 存储驱动契约
 *
 * 支持文件、Redis、数据库等不同存储后端。
 * 实现类负责底层读写，Session 核心类负责业务逻辑。
 */
interface SessionStoreInterface
{
    /**
     * 读取 Session 数据
     *
     * @param string $sessionId
     * @return array<string, mixed>
     */
    public function read(string $sessionId): array;

    /**
     * 写入 Session 数据
     *
     * @param string $sessionId
     * @param array<string, mixed> $data
     */
    public function write(string $sessionId, array $data): void;

    /**
     * 销毁 Session
     *
     * @param string $sessionId
     */
    public function destroy(string $sessionId): void;

    /**
     * 清理过期 Session
     *
     * @param int $maxLifetime 过期秒数
     * @return int 清理数量
     */
    public function gc(int $maxLifetime): int;
}