<?php

declare(strict_types=1);

namespace Framework\Http\Response;

use Framework\Foundation\AppEnvironment;
use Generator;

class StreamResponse extends Response
{
    private Generator $generator;
    private string $format;
    private bool $flush;

    public const FORMAT_NDJSON = 'ndjson';
    public const FORMAT_SSE = 'sse';
    public const FORMAT_TEXT = 'text';

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

    public static function generator(callable $callback, string $format = self::FORMAT_NDJSON): self
    {
        return new self($callback(), $format);
    }

    public static function fromArray(array $items, string $format = self::FORMAT_NDJSON): self
    {
        return new self((function () use ($items) {
            foreach ($items as $item) {
                yield $item;
            }
        })(), $format);
    }

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

    public function getGenerator(): Generator
    {
        return $this->generator;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

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

    public function getContent(): string
    {
        $output = '';
        foreach ($this->generator as $data) {
            $output .= $this->formatData($data);
        }
        return $output;
    }

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

    private function flushOutput(): void
    {
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }
}
