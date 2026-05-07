<?php

declare(strict_types=1);

namespace Framework\Error;

class ErrorHandler
{
    private static bool $registered = false;
    private static bool $debug = true;
    private static ?\Psr\Log\LoggerInterface $logger = null;

    public static function register(bool $debug = true): void
    {
        if (self::$registered) return;
        self::$registered = true;
        self::$debug = $debug;

        error_reporting(E_ALL);

        if ($debug) {
            ini_set('display_errors', '0');
            ini_set('display_startup_errors', '0');

            set_error_handler([self::class, 'handleError']);
            set_exception_handler([self::class, 'handleException']);
            register_shutdown_function([self::class, 'handleShutdown']);
        } else {
            ini_set('display_errors', '0');

            set_exception_handler(function (\Throwable $exception) {
                $code = $exception instanceof \Framework\Exception\HttpException ? $exception->getStatusCode() : 500;
                if (!headers_sent()) {
                    http_response_code($code);
                    echo self::renderProduction($code);
                }
                self::logException($exception);
            });

            set_error_handler(function ($severity, $message, $file, $line) {
                if (!(error_reporting() & $severity)) return false;
                throw new \ErrorException($message, 0, $severity, $file, $line);
            });

            register_shutdown_function([self::class, 'handleShutdown']);
        }
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error === null) return;

        $fatalErrors = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
        if (!in_array($error['type'], $fatalErrors, true)) return;

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $exception = new \ErrorException(
            $error['message'],
            0,
            $error['type'],
            $error['file'],
            $error['line']
        );

        self::handleException($exception);
    }

    public static function setLogger(\Psr\Log\LoggerInterface $logger): void
    {
        self::$logger = $logger;
    }

    public static function handleError(int $severity, string $message, string $file, int $line): bool
    {
        if (!(error_reporting() & $severity)) return false;

        if ($severity === E_DEPRECATED || $severity === E_USER_DEPRECATED) {
            self::logException(new \ErrorException($message, 0, $severity, $file, $line));
            return true;
        }

        $exception = new \ErrorException($message, 0, $severity, $file, $line);
        self::handleException($exception);

        return true;
    }

    public static function handleException(\Throwable $exception): void
    {
        $code = $exception instanceof \Framework\Exception\HttpException ? $exception->getStatusCode() : 500;

        if (!headers_sent() && http_response_code() === false) {
            http_response_code($code);
        }

        if (self::$debug) {
            self::renderDebug($exception);
        } else {
            if (!headers_sent()) {
                echo self::renderProduction($code);
            }
        }

        self::logException($exception);
    }

    public static function logException(\Throwable $exception): void
    {
        if (self::$logger) {
            self::$logger->error($exception->getMessage(), [
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ]);
            return;
        }

        $logFile = paths()->logs('app.log');
        $dir = dirname($logFile);
        if (!is_dir($dir)) @mkdir($dir, 0755, true);

        $timestamp = date('Y-m-d H:i:s');
        $message = sprintf(
            "[%s] %s: %s in %s:%d\nStack trace:\n%s\n",
            $timestamp,
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );

        @file_put_contents($logFile, $message, FILE_APPEND | LOCK_EX);
    }

    public static function log(string $level, string $message, array $context = []): void
    {
        if (self::$logger) {
            self::$logger->log($level, $message, $context);
            return;
        }

        $logFile = paths()->logs('app.log');
        $dir = dirname($logFile);
        if (!is_dir($dir)) @mkdir($dir, 0755, true);

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logLine = "[{$timestamp}] " . strtoupper($level) . ": {$message}{$contextStr}\n";

        @file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }

    private static function renderDebug(\Throwable $exception): void
    {
        $class = get_class($exception);
        $code = $exception instanceof \Framework\Exception\HttpException ? $exception->getStatusCode() : 500;
        $message = htmlspecialchars($exception->getMessage(), ENT_QUOTES, 'UTF-8');
        $file = $exception->getFile();
        $line = $exception->getLine();
        
        $source = self::renderSource($file, $line);
        $requestInfo = self::getRequestInfo();
        $envInfo = self::getEnvInfo();

        echo <<<HTML
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>{$code} Error: {$message}</title>
    <style>
        :root { --bg: #0f172a; --panel: #1e293b; --text: #f1f5f9; --sub: #94a3b8; --accent: #f43f5e; --code-bg: #111827; --line-hl: #334155; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: ui-sans-serif, system-ui, -apple-system, sans-serif; background: var(--bg); color: var(--text); line-height: 1.5; padding: 2rem; }
        .container { max-width: 1200px; margin: 0 auto; }
        header { margin-bottom: 2rem; }
        .error-type { font-size: 0.875rem; color: var(--accent); font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
        .error-message { font-size: 1.875rem; font-weight: 700; margin: 0.5rem 0; color: #fff; }
        .error-loc { color: var(--sub); font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 0.9rem; }
        
        .section { background: var(--panel); border-radius: 12px; border: 1px solid #334155; margin-bottom: 2rem; overflow: hidden; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .section-title { padding: 1rem 1.5rem; background: rgba(255,255,255,0.03); border-bottom: 1px solid #334155; font-size: 0.875rem; font-weight: 600; color: var(--sub); display:flex; justify-content:space-between; }
        
        .code-view { background: var(--code-bg); padding: 1rem 0; font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-size: 0.875rem; overflow-x: auto; }
        .code-line { display: flex; padding: 0 1.5rem; }
        .code-line.highlight { background: var(--line-hl); border-left: 4px solid var(--accent); padding-left: calc(1.5rem - 4px); }
        .line-num { width: 3rem; color: #4b5563; text-align: right; margin-right: 1.5rem; flex-shrink: 0; user-select: none; }
        .line-content { white-space: pre; color: #d1d5db; }
        .code-line.highlight .line-content { color: #fff; }

        .data-table { width: 100%; border-collapse: collapse; font-size: 0.875rem; }
        .data-table td { padding: 0.75rem 1.5rem; border-bottom: 1px solid #334155; vertical-align: top; }
        .data-table tr:last-child td { border-bottom: none; }
        .key { width: 25%; color: var(--sub); font-weight: 600; }
        .val { color: #e2e8f0; word-break: break-all; }
        
        .trace-item { padding: 1rem 1.5rem; border-bottom: 1px solid #334155; }
        .trace-item:last-child { border-bottom: none; }
        .trace-func { font-weight: 600; color: #fff; margin-bottom: 0.25rem; }
        .trace-file { color: var(--sub); font-size: 0.75rem; font-family: ui-monospace, monospace; }

        .tabs { display: flex; background: rgba(0,0,0,0.2); }
        .tab { padding: 0.75rem 1.5rem; cursor: pointer; border-right: 1px solid #334155; color: var(--sub); font-size: 0.875rem; }
        .tab.active { background: var(--panel); color: #fff; font-weight: 600; }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <div class="error-type">{$class}</div>
            <h1 class="error-message">{$message}</h1>
            <div class="error-loc">{$file}:{$line}</div>
        </header>

        <div class="section">
            <div class="section-title">Source Code</div>
            <div class="code-view">
                {$source}
            </div>
        </div>

        <div class="section">
            <div class="section-title">Stack Trace</div>
            <div class="trace-list">
                {$requestInfo}
            </div>
        </div>

        <div class="section">
            <div class="section-title">Environment & Context</div>
            {$envInfo}
        </div>
        
        <div class="section">
            <div class="section-title">Raw Trace</div>
            <div class="trace-item" style="background:#111827">
                <pre style="font-size:0.8rem; color:#94a3b8; white-space:pre-wrap;">{$exception->getTraceAsString()}</pre>
            </div>
        </div>
    </div>
</body>
</html>
HTML;
    }

    private static function renderSource(string $file, int $line): string
    {
        if (!is_file($file)) return '<div class="code-line">File not found.</div>';

        $lines = file($file);
        $start = max(0, $line - 6);
        $end = min(count($lines), $line + 5);
        $output = '';

        for ($i = $start; $i < $end; $i++) {
            $num = $i + 1;
            $content = htmlspecialchars($lines[$i]);
            $isHighlight = $num === $line ? ' highlight' : '';
            $output .= "<div class=\"code-line{$isHighlight}\"><span class=\"line-num\">{$num}</span><span class=\"line-content\">{$content}</span></div>";
        }

        return $output;
    }

    private static function getRequestInfo(): string
    {
        $url = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $method = $_SERVER['REQUEST_METHOD'];
        
        $html = '<table class="data-table">';
        $html .= "<tr><td class='key'>URL</td><td class='val'>{$url}</td></tr>";
        $html .= "<tr><td class='key'>Method</td><td class='val'>{$method}</td></tr>";
        
        if (!empty($_POST)) {
            $post = htmlspecialchars(print_r($_POST, true));
            $html .= "<tr><td class='key'>POST Data</td><td class='val'><pre>{$post}</pre></td></tr>";
        }
        
        if (!empty($_GET)) {
            $get = htmlspecialchars(print_r($_GET, true));
            $html .= "<tr><td class='key'>GET Data</td><td class='val'><pre>{$get}</pre></td></tr>";
        }

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$name] = $value;
            }
        }
        
        if (!empty($headers)) {
            $hStr = htmlspecialchars(print_r($headers, true));
            $html .= "<tr><td class='key'>Headers</td><td class='val'><pre>{$hStr}</pre></td></tr>";
        }

        $html .= '</table>';
        return $html;
    }

    private static function getEnvInfo(): string
    {
        $html = '<table class="data-table">';
        
        if (!empty($_SESSION)) {
            $session = htmlspecialchars(print_r($_SESSION, true));
            $html .= "<tr><td class='key'>Session</td><td class='val'><pre>{$session}</pre></td></tr>";
        }

        if (!empty($_COOKIE)) {
            $cookies = htmlspecialchars(print_r($_COOKIE, true));
            $html .= "<tr><td class='key'>Cookies</td><td class='val'><pre>{$cookies}</pre></td></tr>";
        }

        $html .= "<tr><td class='key'>PHP Version</td><td class='val'>" . PHP_VERSION . "</td></tr>";
        $html .= "<tr><td class='key'>OS</td><td class='val'>" . PHP_OS . "</td></tr>";
        $html .= '</table>';
        return $html;
    }

    public static function renderProduction(int $code): string
    {
        return ErrorPage::render($code);
    }
}
