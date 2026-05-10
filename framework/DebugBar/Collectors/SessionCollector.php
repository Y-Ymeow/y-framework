<?php

declare(strict_types=1);

namespace Framework\DebugBar\Collectors;

use Framework\DebugBar\CollectorInterface;

class SessionCollector implements CollectorInterface
{
    private array $sessionData = [];

    public function getName(): string
    {
        return 'session';
    }

    public function getTab(): array
    {
        return [
            'label' => 'Session',
            'icon' => '🔐',
            'badge' => count($this->sessionData) > 0 ? (string)count($this->sessionData) : null,
        ];
    }

    public function getData(): array
    {
        return [
            'data' => $this->sessionData,
            'count' => count($this->sessionData),
        ];
    }

    public function collect(): void
    {
        $this->sessionData = [];

        if (isset($_SESSION) && is_array($_SESSION)) {
            foreach ($_SESSION as $key => $value) {
                $this->sessionData[] = [
                    'key' => $key,
                    'value' => $this->formatValue($value),
                    'type' => gettype($value),
                ];
            }
        }
    }

    private function formatValue(mixed $value): string
    {
        if (is_string($value)) return $value;
        if (is_numeric($value)) return (string)$value;
        if (is_bool($value)) return $value ? 'true' : 'false';
        if (is_null($value)) return 'null';
        if (is_array($value) || is_object($value)) {
            $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            return mb_substr($json, 0, 500);
        }
        return (string)$value;
    }

    public static function register(): void
    {
        $debugBar = \Framework\DebugBar\DebugBar::getInstance();
        if (!$debugBar->getCollector('session')) {
            $debugBar->addCollector(new self());
        }
    }
}