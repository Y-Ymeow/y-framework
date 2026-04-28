<?php

declare(strict_types=1);

namespace Framework\DebugBar;

use Framework\Http\Request;

class RouteCollector implements CollectorInterface
{
    private array $data = [];

    public function getName(): string
    {
        return 'routes';
    }

    public function getTab(): array
    {
        return [
            'label' => 'Routes',
            'icon' => '🔀',
            'badge' => $this->data['method'] ?? null,
        ];
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function collect(): void
    {
        $request = \Framework\Foundation\Application::getInstance()?->make(Request::class);
        
        if ($request) {
            $route = $request->route();
            $this->data = [
                'matched_route' => $request->getRequestUri(),
                'method' => $request->getMethod(),
                'controller' => $route?->getActionName() ?? 'N/A',
                'parameters' => $route?->parameters() ?? [],
            ];
        }
    }

    public static function register(): void
    {
        $debugBar = DebugBar::getInstance();
        if (!$debugBar->getCollector('routes')) {
            $debugBar->addCollector(new self());
        }
    }
}
