<?php

declare(strict_types=1);

namespace Framework\DebugBar;

use Framework\Events\Hook;
use Framework\Http\Request;
use Framework\Http\Response;
use Framework\Http\StreamedResponse;
use Framework\DebugBar\Components\DebugBarComponent;

class DebugBarListener
{
    private DebugBar $debugBar;

    public function __construct(DebugBar $debugBar)
    {
        $this->debugBar = $debugBar;
    }

    public function register(): void
    {
        Hook::addAction('response.created', [$this, 'onResponseCreated'], 10, 2);
        Hook::addFilter('response.sending', [$this, 'onResponseSending'], 10, 2);
        Hook::addFilter('live.action.completed', [$this, 'onLiveActionCompleted'], 10, 3);
    }

    public function onResponseCreated(Response|StreamedResponse $response, Request $request): void
    {
        if (!config('app.debug', false)) return;

        SqlCollector::register();
        RouteCollector::register();
        RequestCollector::register();

        $this->debugBar->collect();
    }

    public function onResponseSending(Response|StreamedResponse $response, Request $request): Response|StreamedResponse
    {
        if (!config('app.debug', false)) return $response;
        if ($response instanceof StreamedResponse) return $response;

        if ($request->ajax() || str_contains($request->getRequestUri(), '/live')) {
            return $response;
        }

        $sfResponse = $response->getSfResponse();
        $contentType = $sfResponse->headers->get('Content-Type', '');

        if (!str_contains($contentType, 'text/html')) {
            return $response;
        }

        $content = $sfResponse->getContent();

        $component = new DebugBarComponent();
        $injectedHtml = (string)$component;
        if (str_contains($content, '</body>')) {
            $content = str_replace('</body>', $injectedHtml . '</body>', $content);
        } else {
            if (str_contains($content, '</html>')) {
                $content = str_replace('</html>', $injectedHtml . '</html>', $content);
            } else {
                $content .= $injectedHtml;
            }
        }

        $sfResponse->setContent($content);

        return $response;
    }

    public function onLiveActionCompleted(array $response, \Framework\Component\LiveComponent $component, Request $request): array
    {
        if (!config('app.debug', false)) return $response;

        $requestBody = [
            '_component' => $request->input('_component'),
            '_component_id' => $request->input('_component_id'),
            '_action' => $request->input('_action'),
            '_params' => (array)($request->input('_params') ?? []),
        ];

        $responseSummary = [
            'success' => $response['success'] ?? false,
            'patches' => array_keys($response['patches'] ?? []),
            'fragmentCount' => count($response['fragments'] ?? []),
            'operationCount' => count($response['operations'] ?? []),
            'componentUpdateCount' => count($response['componentUpdates'] ?? []),
        ];

        RequestCollector::setPendingRequestData([
            'requestBody' => $requestBody,
            'responseSummary' => $responseSummary,
        ]);

        SqlCollector::register();
        RouteCollector::register();
        RequestCollector::register();
        $this->debugBar->collect();

        $newSnapshot = $this->debugBar->getSnapshot();

        \Framework\Component\LiveEventBus::recordEmittedEvent('debugbar:update', null);

        $dbarListeners = \Framework\Component\LiveEventBus::findListenersForEvent(
            'debugbar:update',
            $component->getComponentId()
        );

        foreach ($dbarListeners as $listener) {
            $stateInfo = \Framework\Component\LiveEventBus::getComponentState($listener['componentId']);
            if (!$stateInfo) continue;

            $class = $stateInfo['class'];
            $comp = new $class();
            $comp->named($listener['componentId']);
            $comp->deserializeState($stateInfo['state']);

            $previousSnapshot = [];
            if (property_exists($comp, 'snapshot')) {
                $ref = new \ReflectionClass($comp);
                $prop = $ref->getProperty('snapshot');
                $previousSnapshot = $prop->getValue($comp);
                if (!is_array($previousSnapshot)) {
                    $previousSnapshot = [];
                }
            }

            $mergedSnapshot = $this->mergeSnapshots($previousSnapshot, $newSnapshot);

            if (property_exists($comp, 'snapshot')) {
                $ref = new \ReflectionClass($comp);
                $prop = $ref->getProperty('snapshot');
                $prop->setValue($comp, $mergedSnapshot);
            }

            $handler = $listener['handler'];
            if (method_exists($comp, $handler)) {
                $ref = new \ReflectionMethod($comp, $handler);
                $params = $ref->getParameters();
                if (!empty($params)) {
                    $comp->$handler(null);
                } else {
                    $comp->$handler();
                }
            }

            $after = $comp->getDataForFrontend();

            $patches = [];
            foreach ($after as $key => $value) {
                $patches[$key] = $value;
            }

            $fragments = [];
            $refreshTargets = method_exists($comp, 'getRefreshFragments') ? $comp->getRefreshFragments() : [];
            if (!empty($refreshTargets)) {
                \Framework\View\FragmentRegistry::reset();
                \Framework\View\FragmentRegistry::setTargets($refreshTargets);
                $comp->render();
                foreach (\Framework\View\FragmentRegistry::getFragments() as $name => $data) {
                    $fragments[] = [
                        'name' => $name,
                        'html' => $data['element']->render(),
                        'mode' => $data['mode'],
                    ];
                }
            }

            if (method_exists($comp, 'getOperations')) {
                $ops = $comp->getOperations();
                if (!empty($ops)) {
                    $response['operations'] = array_merge($response['operations'], $ops);
                }
            }

            $found = false;
            foreach ($response['componentUpdates'] as &$existing) {
                if ($existing['componentId'] === $listener['componentId']) {
                    $existing['state'] = $comp->serializeState();
                    $existing['patches'] = array_merge($existing['patches'], $patches);
                    $existing['fragments'] = array_merge($existing['fragments'], $fragments);
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $response['componentUpdates'][] = [
                    'componentId' => $listener['componentId'],
                    'state' => $comp->serializeState(),
                    'patches' => $patches,
                    'fragments' => $fragments,
                ];
            }
        }

        return $response;
    }

    protected function mergeSnapshots(array $previous, array $new): array
    {
        if (empty($previous)) return $new;
        if (empty($new)) return $previous;

        $merged = $new;

        if (isset($previous['requests']) && is_array($previous['requests'])) {
            $newRequests = $new['requests'] ?? [];
            $merged['requests'] = array_merge($previous['requests'], $newRequests);
            if (count($merged['requests']) > 50) {
                $merged['requests'] = array_slice($merged['requests'], -50);
            }
        }

        if (isset($previous['messages']) && is_array($previous['messages'])) {
            $newMessages = $new['messages'] ?? [];
            $merged['messages'] = array_merge($previous['messages'], $newMessages);
            if (count($merged['messages']) > 100) {
                $merged['messages'] = array_slice($merged['messages'], -100);
            }
        }

        if (isset($previous['debug']) && is_array($previous['debug'])) {
            $newDebug = $new['debug'] ?? [];
            $merged['debug'] = array_merge($previous['debug'], $newDebug);
            if (count($merged['debug']) > 50) {
                $merged['debug'] = array_slice($merged['debug'], -50);
            }
        }

        if (isset($previous['panels']['sql']['data']['queries']) && is_array($previous['panels']['sql']['data']['queries'])) {
            $newQueries = $new['panels']['sql']['data']['queries'] ?? [];
            $merged['panels']['sql']['data']['queries'] = array_merge(
                $previous['panels']['sql']['data']['queries'],
                $newQueries
            );
            if (count($merged['panels']['sql']['data']['queries']) > 100) {
                $merged['panels']['sql']['data']['queries'] = array_slice($merged['panels']['sql']['data']['queries'], -100);
            }
            $prevTotal = (int)($previous['panels']['sql']['data']['total_queries'] ?? 0);
            $newTotal = (int)($new['panels']['sql']['data']['total_queries'] ?? 0);
            $merged['panels']['sql']['data']['total_queries'] = $prevTotal + $newTotal;
        }

        if (isset($previous['panels']['request']['data']['history']) && is_array($previous['panels']['request']['data']['history'])) {
            $newHistory = $new['panels']['request']['data']['history'] ?? [];
            $merged['panels']['request']['data']['history'] = array_merge(
                $previous['panels']['request']['data']['history'],
                $newHistory
            );
            if (count($merged['panels']['request']['data']['history']) > 50) {
                $merged['panels']['request']['data']['history'] = array_slice($merged['panels']['request']['data']['history'], -50);
            }
            $prevTotal = (int)($previous['panels']['request']['data']['total'] ?? 0);
            $newTotal = (int)($new['panels']['request']['data']['total'] ?? 0);
            $merged['panels']['request']['data']['total'] = $prevTotal + $newTotal;
        }

        return $merged;
    }
}
