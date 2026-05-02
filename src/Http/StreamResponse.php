<?php

declare(strict_types=1);

namespace Framework\Http;

use Framework\Foundation\AppEnvironment;
use Generator;

/**
 * StreamResponse 流式响应
 *
 * 基于 Generator 的流式 HTTP 响应，支持 NDJSON、SSE、纯文本三种输出格式。
 * 每个 yield 后自动 flush，确保客户端实时接收数据。
 *
 * ## 输出格式
 *
 * - `FORMAT_NDJSON`：每行一个 JSON 对象，Content-Type: application/x-ndjson
 * - `FORMAT_SSE`：Server-Sent Events 格式，Content-Type: text/event-stream
 * - `FORMAT_TEXT`：纯文本格式，Content-Type: text/plain
 *
 * @http-category Response
 * @http-since 2.0
 *
 * @http-example
 * // NDJSON 流（默认）
 * $gen = (function () {
 *     for ($i = 0; $i < 5; $i++) {
 *         yield ['type' => 'text', 'content' => "chunk $i"];
 *         sleep(1);
 *     }
 *     yield ['type' => 'done'];
 * })();
 * return new StreamResponse($gen);
 *
 * // 使用工厂方法
 * return StreamResponse::generator(fn() => myGenerator());
 * return StreamResponse::fromArray($items);
 * @http-example-end
 */
class StreamResponse extends Response
{
    private Generator $generator;
    private string $format;
    private bool $flush;

    /** NDJSON 格式：每行一个 JSON 对象 */
    public const FORMAT_NDJSON = 'ndjson';

    /** SSE 格式：Server-Sent Events */
    public const FORMAT_SSE = 'sse';

    /** 纯文本格式 */
    public const FORMAT_TEXT = 'text';

    /**
     * 创建流式响应
     * @param Generator $generator 数据生成器，每次 yield 一个数据块
     * @param string $format 输出格式，使用 FORMAT_* 常量
     * @param bool $flush 是否在每个数据块后自动 flush
     * @http-default true
     */
    public function __construct(Generator $generator, string $format = self::FORMAT_NDJSON, bool $flush = true)
    {
        $this->generator = $generator;
        $this->format = $format;
        $this->flush = $flush;
        $this->statusCode = 200;
        $this->statusText = 'OK';

        switch ($format) {
            case self::FORMAT_SSE:
                $this->headers['Content-Type'] = 'text/event-stream';
                break;
            case self::FORMAT_NDJSON:
                $this->headers['Content-Type'] = 'application/x-ndjson';
                break;
            default:
                $this->headers['Content-Type'] = 'text/plain; charset=utf-8';
        }

        $this->headers['Cache-Control'] = 'no-cache';
        $this->headers['Connection'] = 'keep-alive';
        $this->headers['X-Accel-Buffering'] = 'no';
    }

    /**
     * 从回调创建流式响应（回调需返回 Generator）
     * @param callable $callback 返回 Generator 的回调函数
     * @param string $format 输出格式
     * @return static
     * @http-example StreamResponse::generator(fn() => myGenerator(), StreamResponse::FORMAT_SSE)
     */
    public static function generator(callable $callback, string $format = self::FORMAT_NDJSON): self
    {
        return new self($callback(), $format);
    }

    /**
     * 从静态数组创建流式响应
     * @param array $items 数据数组
     * @param string $format 输出格式
     * @return static
     * @http-example StreamResponse::fromArray([['type'=>'text','content'=>'hi'],['type'=>'done']])
     */
    public static function fromArray(array $items, string $format = self::FORMAT_NDJSON): self
    {
        return new self((function () use ($items) {
            foreach ($items as $item) {
                yield $item;
            }
        })(), $format);
    }

    /**
     * 创建纯文本流式响应
     * @param callable $callback 返回文本生成器的回调
     * @param float $delay 每块之间的延迟秒数
     * @return static
     * @http-example StreamResponse::textStream(fn() => $textGen(), 0.05)
     */
    public static function textStream(callable $callback, float $delay = 0.01): self
    {
        return new self((function () use ($callback, $delay) {
            foreach ($callback() as $chunk) {
                if ($delay > 0) {
                    usleep((int)($delay * 1000000));
                }
                yield $chunk;
            }
        })(), self::FORMAT_TEXT);
    }

    /**
     * 获取底层 Generator
     * @return Generator
     */
    public function getGenerator(): Generator
    {
        return $this->generator;
    }

    /**
     * 获取输出格式
     * @return string
     */
    public function getFormat(): string
    {
        return $this->format;
    }

    /**
     * 发送流式响应：发送 headers → 清除输出缓冲 → 逐块输出
     * @return void
     */
    public function send(): void
    {
        if (AppEnvironment::isWasm()) {
            $items = [];
            foreach ($this->generator as $data) {
                $items[] = $data;
            }
            echo json_encode(['stream' => $items], JSON_UNESCAPED_UNICODE);
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

        foreach ($this->generator as $data) {
            echo $this->formatData($data);

            if ($this->flush) {
                $this->flushOutput();
            }
        }
    }

    /**
     * 收集所有 Generator 数据为字符串（会消费整个 Generator）
     * @return string
     */
    public function getContent(): string
    {
        $output = '';
        foreach ($this->generator as $data) {
            $output .= $this->formatData($data);
        }
        return $output;
    }

    /** @internal */
    private function formatData(mixed $data): string
    {
        switch ($this->format) {
            case self::FORMAT_SSE:
                return $this->formatSse($data);
            case self::FORMAT_NDJSON:
                return json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
            default:
                return is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE);
        }
    }

    /** @internal */
    private function formatSse(mixed $data): string
    {
        if (is_array($data)) {
            $event = $data['event'] ?? 'message';
            $id = $data['id'] ?? '';
            $data = $data['data'] ?? $data;

            $output = '';
            if ($id) {
                $output .= "id: {$id}\n";
            }
            $output .= "event: {$event}\n";
            $output .= "data: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
            return $output;
        }

        return "data: " . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n\n";
    }

    /** @internal */
    private function flushOutput(): void
    {
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }
}
