<?php

declare(strict_types=1);

namespace Framework\Component\Live\Attribute;

use Attribute;

/**
 * LiveStream — 标记返回流式响应的 LiveAction
 *
 * 将方法标记为流式 Action，返回 StreamResponse 对象。
 * 前端通过 /live/stream 端点调用，使用 ReadableStream 逐块接收数据。
 *
 * ## 参数
 *
 * - `format`: 输出格式，默认 'ndjson'。可选 'ndjson'、'sse'、'text'
 *
 * @live-category Attribute
 * @live-since 2.0
 *
 * @example
 * #[LiveStream(format: 'ndjson')]
 * public function chatStream(): StreamResponse
 * {
 *     return StreamBuilder::create()
 *         ->thinking('思考中...')
 *         ->each($this->generateTokens(), fn($token) => StreamBuilder::textChunk($token))
 *         ->done();
 * }
 */
#[Attribute(Attribute::TARGET_METHOD)]
class LiveStream
{
    public function __construct(
        public string $format = 'ndjson'
    ) {
    }
}
