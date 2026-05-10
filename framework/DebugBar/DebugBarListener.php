<?php

declare(strict_types=1);

namespace Framework\DebugBar;

use Framework\Events\Hook;
use Framework\Events\ResponseCreatedEvent;
use Framework\Foundation\Application;

class DebugBarListener
{
    private DebugBar $debugBar;

    public function __construct(DebugBar $debugBar)
    {
        $this->debugBar = $debugBar;
    }

    public function register(): void
    {
        Hook::getInstance()->on('response.created', [$this, 'onResponseCreated'], 10);
        Hook::getInstance()->on('live.action.completed', [$this, 'onLiveActionCompleted'], 10);
    }

public function onResponseCreated(ResponseCreatedEvent $event): void
    {
        if (!Application::isDebug()) return;

        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        if (str_contains($requestUri, '/_debug')) return;

        // Also skip Live actions from DebugPage (they hit /live/action, not /_debug)
        $component = $event->getRequest()->input('_component', '');
        if (str_contains($component, 'DebugPage')) return;

        SqlCollector::register();
        RouteCollector::register();
        RequestCollector::register();
        \Framework\DebugBar\Collectors\SessionCollector::register();

        $this->debugBar->collect();
    }

    public function onLiveActionCompleted(\Framework\Events\LiveActionEvent $event): void
    {
        if (!Application::isDebug()) return;

        $component = $event->getComponent();
        if (str_contains(get_class($component), 'DebugPage')) return;

        $request = $event->getRequest();

        $response = $event->getResponse();
        $request = $event->getRequest();

        RequestCollector::setPendingRequestData([
            'requestBody' => [
                '_component' => $request->input('_component'),
                '_component_id' => $request->input('_component_id'),
                '_action' => $request->input('_action'),
                '_params' => (array)($request->input('_params') ?? []),
            ],
            'responseSummary' => [
                'success' => $response['success'] ?? false,
                'patches' => array_keys($response['patches'] ?? []),
                'fragmentCount' => count($response['fragments'] ?? []),
                'operationCount' => count($response['operations'] ?? []),
            ],
        ]);

        SqlCollector::register();
        RouteCollector::register();
        RequestCollector::register();
        \Framework\DebugBar\Collectors\SessionCollector::register();
        $this->debugBar->collect();
    }
}