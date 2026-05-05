<?php

declare(strict_types=1);

namespace Framework\Http\Response;

use Framework\Foundation\AppEnvironment;

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

    public static function create(): self
    {
        return new self();
    }

    public function event(string $event, mixed $data, ?string $id = null): self
    {
        $this->events[] = [
            'event' => $event,
            'data' => $data,
            'id' => $id,
        ];
        return $this;
    }

    public function keepAlive(int $seconds): self
    {
        $this->keepAlive = $seconds;
        return $this;
    }

    public function onInterval(callable $callback, int $intervalMs = 1000): self
    {
        $this->onInterval = $callback;
        $this->intervalMs = $intervalMs;
        return $this;
    }

    public function maxExecTime(int $seconds): self
    {
        $this->maxExecTime = $seconds;
        return $this;
    }

    public function subscribe(string ...$channels): self
    {
        $this->channels = array_merge($this->channels, $channels);
        return $this;
    }

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

    public function getContent(): string
    {
        $output = '';
        foreach ($this->events as $event) {
            $output .= $this->formatEvent($event['event'], $event['data'], $event['id']);
        }
        return $output;
    }

    public static function simple(callable $callback, int $intervalMs = 1000): self
    {
        return self::create()->onInterval($callback, $intervalMs);
    }

    private function sendEvent(string $event, mixed $data, ?string $id = null): void
    {
        echo $this->formatEvent($event, $data, $id);

        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();
    }

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
