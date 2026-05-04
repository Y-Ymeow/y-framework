<?php

declare(strict_types=1);

namespace Framework\Http\Response;

use Framework\Foundation\AppEnvironment;

/**
 * 重定向响应
 */
class RedirectResponse extends Response
{
    public function __construct(string $url, int $status = 302, array $headers = [])
    {
        if (AppEnvironment::isWasm()) {
            $content = json_encode([
                '_redirect' => true,
                'url' => $url,
                'status' => $status,
            ], JSON_UNESCAPED_UNICODE);
            $headers['Content-Type'] = 'application/json';
        } else {
            $content = '';
            $headers['Location'] = $url;
        }

        parent::__construct($content, $status, $headers);
    }

    public function getTargetUrl(): string
    {
        return $this->headers['Location'] ?? '';
    }
}