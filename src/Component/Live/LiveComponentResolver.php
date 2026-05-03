<?php

declare(strict_types=1);

namespace Framework\Component\Live;

use Framework\Foundation\AppEnvironment;
use Framework\Http\Request;
use Framework\Http\Response;
use Framework\Http\Session;
use Framework\Http\StreamResponse;
use Framework\Http\SseResponse;
use Framework\Routing\Attribute\Route;
use Framework\Routing\Attribute\RouteGroup;
use Framework\View\FragmentRegistry;

#[RouteGroup('/live', name: 'live')]
class LiveComponentResolver
{
    #[Route('/update', ['POST'], name: 'live.update')]
    public function handle(Request $request): Response
    {
        if (!AppEnvironment::isWasm()) {
            $session = new Session();
            $csrfToken = $request->header('x-csrf-token')
                ?? $request->input('_token', '');

            if (!$session->verifyToken((string) $csrfToken)) {
                return Response::json(['success' => false, 'error' => 'Invalid CSRF token'], 419);
            }
        }

        $componentClass = $request->header('x-live-component')
            ?? $request->input('_component');

        $action = $request->header('x-live-action')
            ?? $request->input('_action');

        $state = $request->input('_state', '');
        $publicData = $request->input('_data', []);
        $params = $request->input('_params', []);

        if (!is_array($publicData)) $publicData = [];
        if (!is_array($params)) $params = [];

        if (empty($componentClass) || empty($action)) {
            return Response::json(['success' => false, 'error' => 'Missing component or action'], 400);
        }

        if (!class_exists($componentClass)) {
            return Response::json(['success' => false, 'error' => "Component [{$componentClass}] not found"], 404);
        }

        try {
            LiveEventBus::reset();

            $components = $request->input('_components');
            if (!is_array($components)) {
                $components = [];
            }

            foreach ($components as $compData) {
                if (empty($compData['class']) || empty($compData['id']) || empty($compData['state'])) {
                    continue;
                }

                LiveEventBus::storeComponentState(
                    $compData['id'],
                    $compData['class'],
                    $compData['state']
                );
            }

            $component = app()->makeWith($componentClass, $params);

            if (!($component instanceof LiveComponent)) {
                return Response::json(['success' => false, 'error' => 'Invalid component'], 400);
            }

            $requestedComponentId = $request->input('_component_id', '');
            if (!empty($requestedComponentId)) {
                $component->named($requestedComponentId);
            }

            if (!empty($state)) {
                $component->deserializeState($state);
            }

            $component->fillPublicProperties($publicData);

            // 只有首次请求执行 mount（无 _state 表示首次请求）
            if (empty($state) && method_exists($component, 'mount')) {
                $component->mount($params);
            }

            $before = $component->getDataForFrontend();

            $result = $component->callAction($action, $params);

            if ($result instanceof StreamResponse) {
                return Response::json([
                    'success' => false,
                    'error' => 'Stream actions must use /live/stream endpoint',
                ], 400);
            }

            if ($result instanceof SseResponse) {
                return $result;
            }

            $after = $component->getDataForFrontend();

            $patches = [];
            foreach ($after as $key => $value) {
                if (!array_key_exists($key, $before) || $before[$key] !== $value) {
                    $patches[$key] = $value;
                }
            }

            $newState = $component->serializeState();

            $response = [
                'success' => true,
                'component' => $componentClass,
                'action' => $action,
                'state' => $newState,
                'patches' => $patches,
                'domPatches' => [],
                'fragments' => [],
                'operations' => [],
                'componentUpdates' => [],
            ];

            $emittedEvents = LiveEventBus::getEmittedEvents();
            foreach ($emittedEvents as $emittedEvent) {
                $listeners = LiveEventBus::findListenersForEvent(
                    $emittedEvent['event'],
                    $component->getComponentId()
                );

                foreach ($listeners as $listener) {
                    $this->processComponentUpdate($listener['componentId'], $response, function ($comp) use ($listener, $emittedEvent) {
                        $handler = $listener['handler'];
                        if (method_exists($comp, $handler)) {
                            $comp->$handler($emittedEvent['data']);
                        }
                    });
                }
            }

            $manualUpdates = method_exists($component, 'getManualUpdates') ? $component->getManualUpdates() : [];
            foreach ($manualUpdates as $targetId => $patchData) {
                $this->processComponentUpdate($targetId, $response, function ($comp) use ($patchData) {
                    if (method_exists($comp, 'deserializeState')) {
                        $ref = new \ReflectionClass($comp);
                        foreach ($patchData as $key => $val) {
                            if ($ref->hasProperty($key)) {
                                $prop = $ref->getProperty($key);
                                $prop->setValue($comp, $val);
                            }
                        }
                    }
                });
            }

            $refreshTargets = method_exists($component, 'getRefreshFragments') ? $component->getRefreshFragments() : [];
            if (!empty($refreshTargets)) {
                FragmentRegistry::reset();
                FragmentRegistry::setTargets($refreshTargets);
                $component->render();

                foreach (FragmentRegistry::getFragments() as $name => $data) {
                    $response['fragments'][] = [
                        'name' => $name,
                        'html' => $data['element']->render(),
                        'mode' => $data['mode']
                    ];
                }
            }

            $response = \Framework\Events\Hook::filter('live.action.completed', $response, $component, $request);

            if ($result instanceof \Framework\View\LiveResponse) {
                $lr = $result->toArray();
                if (!empty($lr['domPatches'])) $response['domPatches'] = $lr['domPatches'];
                if (!empty($lr['fragments'])) $response['fragments'] = array_merge($response['fragments'], $lr['fragments']);
                if (!empty($lr['operations'])) $response['operations'] = array_merge($response['operations'], $lr['operations']);
            } elseif (is_array($result) && isset($result['operations'])) {
                $response['operations'] = array_merge($response['operations'], $result['operations']);
            }

            if (method_exists($component, 'getOperations')) {
                $componentOps = $component->getOperations();
                if (!empty($componentOps)) {
                    $response['operations'] = array_merge($response['operations'], $componentOps);
                }
            }

            return Response::json($response);
        } catch (\Throwable $e) {
            return Response::json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    #[Route('/stream', ['POST'], name: 'live.stream')]
    public function stream(Request $request): Response
    {
        if (!AppEnvironment::isWasm()) {
            $session = new Session();
            $csrfToken = $request->header('x-csrf-token')
                ?? $request->input('_token', '');

            if (!$session->verifyToken((string) $csrfToken)) {
                return Response::json(['success' => false, 'error' => 'Invalid CSRF token'], 419);
            }
        }

        $componentClass = $request->header('x-live-component')
            ?? $request->input('_component');

        $action = $request->header('x-live-action')
            ?? $request->input('_action');

        $state = $request->input('_state', '');
        $publicData = $request->input('_data', []);
        $params = $request->input('_params', []);

        if (!is_array($publicData)) $publicData = [];
        if (!is_array($params)) $params = [];

        if (empty($componentClass) || empty($action)) {
            return Response::json(['success' => false, 'error' => 'Missing component or action'], 400);
        }

        if (!class_exists($componentClass)) {
            return Response::json(['success' => false, 'error' => "Component [{$componentClass}] not found"], 404);
        }

        try {
            LiveEventBus::reset();

            $components = $request->input('_components');
            if (!is_array($components)) {
                $components = [];
            }

            foreach ($components as $compData) {
                if (empty($compData['class']) || empty($compData['id']) || empty($compData['state'])) {
                    continue;
                }

                LiveEventBus::storeComponentState(
                    $compData['id'],
                    $compData['class'],
                    $compData['state']
                );
            }

            $component = app()->makeWith($componentClass, $params);

            if (!($component instanceof LiveComponent)) {
                return Response::json(['success' => false, 'error' => 'Invalid component'], 400);
            }

            $requestedComponentId = $request->input('_component_id', '');
            if (!empty($requestedComponentId)) {
                $component->named($requestedComponentId);
            }

            if (!empty($state)) {
                $component->deserializeState($state);
            }

            $component->fillPublicProperties($publicData);

            // 只有首次请求执行 mount（无 _state 表示首次请求）
            if (empty($state) && method_exists($component, 'mount')) {
                $component->mount($params);
            }

            $result = $component->callAction($action, $params);

            if ($result instanceof StreamResponse) {
                return $result;
            }

            if ($result instanceof SseResponse) {
                return $result;
            }

            return Response::json([
                'success' => false,
                'error' => 'Action does not return a streamable response. Use /live/update for non-stream actions.',
            ], 400);
        } catch (\Throwable $e) {
            return Response::json([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    #[Route('/navigate', ['POST'], name: 'live.navigate')]
    public function navigate(Request $request): Response
    {
        $url = $request->input('url', '');
        if (empty($url)) {
            return Response::json(['error' => 'Missing URL'], 400);
        }

        try {
            $app = \Framework\Foundation\Application::getInstance();
            if (!$app) {
                throw new \RuntimeException('Application not initialized');
            }
            $router = $app->make(\Framework\Routing\Router::class);

            $path = parse_url($url, PHP_URL_PATH) ?? $url;
            $subRequest = Request::create($path, 'GET');

            $app->instance(Request::class, $subRequest);

            $response = $router->dispatch($subRequest);

            if (!$response instanceof Response) {
                return Response::json(['error' => 'Invalid response'], 500);
            }

            $html = $response->getContent() ?? '';
            $fragments = $this->extractNavigateFragments($html);
            $title = $this->extractTitle($html);

            return Response::json([
                'url' => $url,
                'title' => $title,
                'fragments' => $fragments,
            ]);
        } catch (\Throwable $e) {
            $logFile = __DIR__ . '/../../storage/logs/navigate-error.log';
            @mkdir(dirname($logFile), 0755, true);
            @file_put_contents($logFile, date('Y-m-d H:i:s') . ' - ' . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n\n", FILE_APPEND);

            return Response::json([
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ], 500);
        }
    }

    #[Route('/intl', ['POST'], name: 'live.intl')]
    public function intl(Request $request): Response
    {
        $keys = $request->input('keys', []);
        if (!is_array($keys)) {
            $keys = [];
        }

        $locale = $request->input('locale', '');
        if (!empty($locale)) {
            \Framework\Intl\Translator::setLocale($locale);
        }

        $translations = \Framework\Intl\Translator::getMany($keys);

        return Response::json([
            'success' => true,
            'locale' => \Framework\Intl\Translator::getLocale(),
            'translations' => $translations,
        ]);
    }

    private function extractNavigateFragments(string $html): array
    {
        $fragments = [];
        $dom = \Framework\Support\Dom::load($html);

        $nodes = $dom->query('//*[@data-navigate-fragment]');

        foreach ($nodes as $node) {
            if ($node instanceof \DOMElement) {
                $fragmentName = $node->getAttribute('data-navigate-fragment');
                $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '', $fragmentName);

                if (empty($safeName)) {
                    continue;
                }

                $fragments[] = [
                    'name' => $safeName,
                    'html' => $dom->getInnerHtml($node),
                ];
            }
        }

        if (empty($fragments)) {
            $bodyContent = $dom->getBodyContent();
            if (!empty($bodyContent)) {
                $fragments[] = [
                    'name' => 'body',
                    'html' => $bodyContent,
                ];
            }
        }

        return $fragments;
    }

    private function extractTitle(string $html): string
    {
        return \Framework\Support\Dom::load($html)->getTitle();
    }

    private function processComponentUpdate(string $componentId, array &$response, callable $callback): void
    {
        $stateInfo = LiveEventBus::getComponentState($componentId);
        if (!$stateInfo) return;

        $class = $stateInfo['class'];
        $comp = new $class();
        $comp->named($componentId);
        $comp->deserializeState($stateInfo['state']);

        $callback($comp);
        $after = $comp->getDataForFrontend();

        $patches = [];
        foreach ($after as $key => $value) {
            $patches[$key] = $value;
        }

        $fragments = [];
        $refreshTargets = method_exists($comp, 'getRefreshFragments') ? $comp->getRefreshFragments() : [];
        if (!empty($refreshTargets)) {
            FragmentRegistry::reset();
            FragmentRegistry::setTargets($refreshTargets);
            $comp->render();
            foreach (FragmentRegistry::getFragments() as $name => $data) {
                $fragments[] = [
                    'name' => $name,
                    'html' => $data['element']->render(),
                    'mode' => $data['mode']
                ];
            }
        }

        if (method_exists($comp, 'getOperations')) {
            $ops = $comp->getOperations();
            if (!empty($ops)) {
                $response['operations'] = array_merge($response['operations'], $ops);
            }
        }

        foreach ($response['componentUpdates'] as &$existing) {
            if ($existing['componentId'] === $componentId) {
                $existing['state'] = $comp->serializeState();
                $existing['patches'] = array_merge($existing['patches'], $patches);
                $existing['fragments'] = array_merge($existing['fragments'], $fragments);
                return;
            }
        }

        $response['componentUpdates'][] = [
            'componentId' => $componentId,
            'state' => $comp->serializeState(),
            'patches' => $patches,
            'fragments' => $fragments,
        ];
    }
}
