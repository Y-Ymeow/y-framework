<?php

declare(strict_types=1);

namespace Framework\Http\Response;

use Framework\Foundation\AppEnvironment;

class StreamedResponse extends Response
{
    protected $callback;
    protected bool $streamed = false;

    public function __construct(callable $callback, int $status = 200, array $headers = [])
    {
        $this->callback = $callback;
        $this->statusCode = $status;
        $this->headers = $headers;
        $this->statusText = self::$statusTexts[$status] ?? 'Unknown';
    }

    public function send(): void
    {
        if ($this->streamed) {
            return;
        }

        $this->streamed = true;

        if (AppEnvironment::isWasm()) {
            ob_start();
            call_user_func($this->callback);
            $this->content = ob_get_clean();
            echo $this->content;
            return;
        }

        $this->sendHeaders();

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        while (ob_get_level() > 0) {
            ob_end_flush();
        }

        call_user_func($this->callback);
    }

    public function getContent(): string
    {
        if (!$this->streamed) {
            ob_start();
            call_user_func($this->callback);
            return ob_get_clean();
        }

        return $this->content;
    }

    public function setCallback(callable $callback): self
    {
        $this->callback = $callback;
        return $this;
    }
}
