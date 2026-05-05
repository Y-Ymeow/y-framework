<?php

declare(strict_types=1);

namespace Framework\Component\Live\Stream;

use Framework\Http\Response\StreamResponse;
use Generator;

/**
 * StreamBuilder 流式响应构建器
 *
 * 流式构建 StreamResponse 的流畅接口，支持文本、HTML、JSON、进度条、
 * AI 思考状态、工具调用等语义化方法。
 *
 * ## 延迟执行
 *
 * `each()` 方法不会立即遍历 Generator，而是在 `build()` 时组合为
 * 延迟 Generator，确保流式输出时才逐块执行。
 *
 * @live-category Stream
 * @live-since 2.0
 *
 * @example
 * return StreamBuilder::create()
 *     ->thinking('正在思考...')
 *     ->each($this->generateTokens(), fn($token) => StreamBuilder::textChunk($token))
 *     ->done(['message' => $message])
 *     ->build();
 *
 * @example
 * return StreamBuilder::create()
 *     ->text('开始处理...')
 *     ->each(range(1, 100), fn($i) => StreamBuilder::progressChunk($i, 100))
 *     ->done(['result' => '处理完成！'])
 *     ->build();
 */
class StreamBuilder
{
    private array $items = [];
    private array $generators = [];
    private string $format = StreamResponse::FORMAT_NDJSON;

    private function __construct()
    {
    }

    /**
     * 创建构建器实例
     * @return static
     * @live-example StreamBuilder::create()->text('hello')->done()->build()
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * 设置输出格式
     * @param string $format 输出格式，使用 StreamResponse::FORMAT_* 常量
     * @return static
     */
    public function format(string $format): self
    {
        $this->format = $format;
        return $this;
    }

    /**
     * 添加文本块
     * @param string $content 文本内容
     * @param array $extra 附加字段
     * @return static
     * @live-example StreamBuilder::create()->text('Hello World')->done()->build()
     */
    public function text(string $content, array $extra = []): self
    {
        $this->items[] = array_merge(['type' => 'text', 'content' => $content], $extra);
        return $this;
    }

    /**
     * 添加 HTML 块
     * @param string $html HTML 内容
     * @param array $extra 附加字段
     * @return static
     */
    public function html(string $html, array $extra = []): self
    {
        $this->items[] = array_merge(['type' => 'html', 'content' => $html], $extra);
        return $this;
    }

    /**
     * 添加 JSON 数据块
     * @param array $data 数据
     * @param array $extra 附加字段
     * @return static
     */
    public function json(array $data, array $extra = []): self
    {
        $this->items[] = array_merge(['type' => 'data', 'data' => $data], $extra);
        return $this;
    }

    /**
     * 添加进度更新
     * @param int $current 当前进度
     * @param int $total 总数
     * @param string|null $message 进度消息
     * @return static
     * @live-example StreamBuilder::create()->progress(50, 100, '处理中...')->done()->build()
     */
    public function progress(int $current, int $total, ?string $message = null): self
    {
        $item = [
            'type' => 'progress',
            'current' => $current,
            'total' => $total,
            'percent' => $total > 0 ? round($current / $total * 100, 1) : 0,
        ];
        if ($message !== null) {
            $item['message'] = $message;
        }
        $this->items[] = $item;
        return $this;
    }

    /**
     * 添加错误块
     * @param string $message 错误消息
     * @param int $code 错误码
     * @return static
     */
    public function error(string $message, int $code = 0): self
    {
        $this->items[] = [
            'type' => 'error',
            'message' => $message,
            'code' => $code,
        ];
        return $this;
    }

    /**
     * 添加完成标记
     * @param array $data 附加数据
     * @return static
     * @live-example StreamBuilder::create()->text('done')->done(['result'=>'ok'])->build()
     */
    public function done(array $data = []): self
    {
        $this->items[] = array_merge(['type' => 'done'], $data);
        return $this;
    }

    /**
     * 添加 AI 思考状态块
     * @param string $thought 思考内容
     * @return static
     */
    public function thinking(string $thought): self
    {
        $this->items[] = [
            'type' => 'thinking',
            'content' => $thought,
        ];
        return $this;
    }

    /**
     * 添加工具调用块
     * @param string $tool 工具名称
     * @param array $args 调用参数
     * @return static
     */
    public function toolCall(string $tool, array $args = []): self
    {
        $this->items[] = [
            'type' => 'tool_call',
            'tool' => $tool,
            'args' => $args,
        ];
        return $this;
    }

    /**
     * 延迟遍历可迭代对象（Generator 在 send() 时才执行）
     * @param iterable|Generator $items 可迭代对象或 Generator
     * @param callable $callback 对每个元素调用的回调，需返回数组
     * @return static
     * @live-example StreamBuilder::create()->each($generator, fn($chunk) => StreamBuilder::textChunk($chunk))->done()->build()
     */
    public function each(iterable|Generator $items, callable $callback): self
    {
        $this->generators[] = [
            'items' => $items,
            'callback' => $callback,
        ];
        return $this;
    }

    /**
     * 添加原始数据块
     * @param array $item 数据数组
     * @return static
     */
    public function raw(array $item): self
    {
        $this->items[] = $item;
        return $this;
    }

    /**
     * 条件添加
     * @param bool $condition 条件
     * @param callable $callback 回调
     * @return static
     */
    public function when(bool $condition, callable $callback): self
    {
        if ($condition) {
            $result = $callback($this);
            if ($result instanceof self) {
                return $result;
            }
        }
        return $this;
    }

    /**
     * 构建 StreamResponse（延迟执行，Generator 在 send() 时才遍历）
     * @return StreamResponse
     */
    public function build(): StreamResponse
    {
        $generators = $this->generators;
        $staticItems = $this->items;

        $gen = (function () use ($staticItems, $generators): Generator {
            foreach ($staticItems as $item) {
                yield $item;
            }

            foreach ($generators as $genDef) {
                $items = $genDef['items'];
                $callback = $genDef['callback'];

                foreach ($items as $item) {
                    $result = $callback($item);
                    if (is_array($result)) {
                        yield $result;
                    }
                }
            }
        })();

        return new StreamResponse($gen, $this->format);
    }

    /**
     * 创建文本块数据
     * @param string $content 文本内容
     * @return array
     * @live-example StreamBuilder::textChunk('hello')
     */
    public static function textChunk(string $content): array
    {
        return ['type' => 'text', 'content' => $content];
    }

    /**
     * 创建进度块数据
     * @param int $current 当前进度
     * @param int $total 总数
     * @return array
     */
    public static function progressChunk(int $current, int $total): array
    {
        return [
            'type' => 'progress',
            'current' => $current,
            'total' => $total,
            'percent' => $total > 0 ? round($current / $total * 100, 1) : 0,
        ];
    }

    /**
     * 创建完成块数据
     * @param array $data 附加数据
     * @return array
     */
    public static function doneChunk(array $data = []): array
    {
        return array_merge(['type' => 'done'], $data);
    }

    /**
     * 创建错误块数据
     * @param string $message 错误消息
     * @param int $code 错误码
     * @return array
     */
    public static function errorChunk(string $message, int $code = 0): array
    {
        return ['type' => 'error', 'message' => $message, 'code' => $code];
    }
}
