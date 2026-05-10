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

        SqlCollector::register();
        RouteCollector::register();
        RequestCollector::register();
        \Framework\DebugBar\Collectors\SessionCollector::register();

        $this->debugBar->collect();
    }

    public function onLiveActionCompleted(\Framework\Events\LiveActionEvent $event): void
    {
        if (!Application::isDebug()) return;

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