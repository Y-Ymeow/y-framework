<?php

declare(strict_types=1);

namespace Framework\Debug;

use Throwable;
use Framework\Http\Response;

final class ErrorHandler
{
    public static function handle(Throwable $e, bool $debug): Response
    {
        if (!$debug) {
            return new Response("Server Error", 500);
        }

        $html = self::renderDebugPage($e);
        return new Response($html, 500);
    }

    private static function renderDebugPage(Throwable $e): string
    {
        $file = $e->getFile();
        $line = $e->getLine();
        $codeSnippet = self::getCodeSnippet($file, $line);
        $trace = $e->getTraceAsString();

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Exception: {$e->getMessage()}</title>
    <style>
        body { font-family: sans-serif; background: #f8f9fa; color: #333; margin: 0; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #d9534f; margin-top: 0; font-size: 24px; }
        .file { font-weight: bold; color: #666; margin-bottom: 20px; word-break: break-all; }
        pre { background: #2d2d2d; color: #ccc; padding: 15px; overflow: auto; border-radius: 4px; line-height: 1.5; }
        .code-line { display: block; }
        .highlight { background: #444; color: #fff; display: block; padding: 0 5px; border-left: 3px solid #d9534f; }
        .trace { margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; white-space: pre-wrap; font-family: monospace; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Unhandled Exception: {$e->getMessage()}</h1>
        <div class="file">{$file} : Line {$line}</div>
        <pre>{$codeSnippet}</pre>
        <div class="trace"><strong>Stack Trace:</strong><br>{$trace}</div>
    </div>
</body>
</html>
HTML;
    }

    private static function getCodeSnippet(string $file, int $line): string
    {
        if (!is_file($file)) return "Source not available.";
        
        $lines = file($file);
        $start = max(0, $line - 5);
        $end = min(count($lines), $line + 5);
        
        $snippet = "";
        for ($i = $start; $i < $end; $i++) {
            $currentLine = $i + 1;
            $content = htmlspecialchars($lines[$i]);
            if ($currentLine === $line) {
                $snippet .= "<span class='highlight'>{$currentLine} | {$content}</span>";
            } else {
                $snippet .= "<span class='code-line'>{$currentLine} | {$content}</span>";
            }
        }
        return $snippet;
    }
}
