<?php

declare(strict_types=1);

namespace Framework\DebugBar;

use Framework\Http\Request;

class RequestCollector implements CollectorInterface
{
    private array $requests = [];
    private static ?array $pendingRequestData = null;

    public function getName(): string
    {
        return 'request';
    }

    public function getTab(): array
    {
        $count = count($this->requests);
        return [
            'label' => 'Requests',
            'icon' => '🌐',
            'badge' => $count > 0 ? (string)$count : null,
        ];
    }

    public function getData(): array
    {
        return [
            'history' => array_reverse($this->requests),
            'total' => count($this->requests),
        ];
    }

    public static function setPendingRequestData(array $data): void
    {
        self::$pendingRequestData = $data;
    }

    public static function getPendingRequestData(): ?array
    {
        return self::$pendingRequestData;
    }

    public static function clearPendingRequestData(): void
    {
        self::$pendingRequestData = null;
    }

    public function collect(): void
    {
        $debugBar = DebugBar::getInstance();
        $key = $debugBar->getKey();

        $this->requests = $existingData['requests'] ?? [];

        $currentUrl = $_SERVER['REQUEST_URI'] ?? '/';
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') 
                  || isset($_SERVER['HTTP_X_DEBUG_KEY']);
        $isLive = str_contains($currentUrl, '/live');

        $entry = [
            'id' => bin2hex(random_bytes(8)), // 唯一的请求 ID
            'url' => $currentUrl,
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'GET',
            'status' => http_response_code() ?: 200,
            'time' => date('H:i:s'),
            'duration' => number_format((microtime(true) - $debugBar->getStartTime()) * 1000, 2) . 'ms',
            'type' => $isLive ? 'live' : ($isAjax ? 'ajax' : 'document'),
        ];

        if (self::$pendingRequestData !== null) {
            if (isset(self::$pendingRequestData['requestBody'])) {
                $entry['requestBody'] = self::$pendingRequestData['requestBody'];
            }
            if (isset(self::$pendingRequestData['responseSummary'])) {
                $entry['responseSummary'] = self::$pendingRequestData['responseSummary'];
            }
        }

        $this->requests[] = $entry;

        if (count($this->requests) > 50) {
            $this->requests = array_slice($this->requests, -50);
        }

        $existingData['requests'] = $this->requests;

        self::$pendingRequestData = null;
    }

    public static function register(): void
    {
        $debugBar = DebugBar::getInstance();
        if (!$debugBar->getCollector('request')) {
            $debugBar->addCollector(new self());
        }
    }
}
