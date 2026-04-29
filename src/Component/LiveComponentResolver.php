<?php

declare(strict_types=1);

namespace Framework\Component;

use Framework\Http\Request;
use Framework\Http\Response;
use Framework\Http\Session;
use Framework\Routing\Attribute\Route;
use Framework\Routing\Attribute\RouteGroup;
use Framework\View\FragmentRegistry;

#[RouteGroup('/live', name: 'live')]
class LiveComponentResolver
{
    #[Route('/update', ['POST'], name: 'live.update')]
    public function handle(Request $request): Response
    {
        $session = new Session();
        $csrfToken = $request->header('x-csrf-token')
            ?? $request->input('_token', '');

        if (!$session->verifyToken((string) $csrfToken)) {
            return Response::json(['success' => false, 'error' => 'Invalid CSRF token'], 419);
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
            \Framework\Component\LiveEventBus::reset();

            $components = $request->input('_components');
            if (!is_array($components)) {
                $components = [];
            }

            foreach ($components as $compData) {
                if (empty($compData['class']) || empty($compData['id']) || empty($compData['state'])) {
                    continue;
                }

                \Framework\Component\LiveEventBus::storeComponentState(
                    $compData['id'],
                    $compData['class'],
                    $compData['state']
                );
            }

            $component = new $componentClass();

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

            // 使用 _data 字段恢复公开属性
            $component->fillPublicProperties($publicData);

            $before = $component->getDataForFrontend();

            // 执行 Action，传入专门的业务参数 _params
            $result = $component->callAction($action, $params);

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

            // 1. 处理事件触发的更新
            $emittedEvents = \Framework\Component\LiveEventBus::getEmittedEvents();
            foreach ($emittedEvents as $emittedEvent) {
                $listeners = \Framework\Component\LiveEventBus::findListenersForEvent(
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

            // 2. 处理手动指定的更新 (updateComponent)
            $manualUpdates = method_exists($component, 'getManualUpdates') ? $component->getManualUpdates() : [];
            foreach ($manualUpdates as $targetId => $patchData) {
                $this->processComponentUpdate($targetId, $response, function ($comp) use ($patchData) {
                    // 简单地应用补丁
                    if (method_exists($comp, 'deserializeState')) {
                        // 这里我们实际上是想更新组件的属性
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

            // 3. 处理当前组件的片段刷新
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

            // 4. 触发 live.action.completed 钩子，允许外部系统（如 DebugBar）响应
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

    #[Route('/navigate', ['POST'], name: 'live.navigate')]
    public function navigate(Request $request): Response
    {
        $url = $request->input('url', '');
        if (empty($url)) {
            return Response::json(['error' => 'Missing URL'], 400);
        }

        try {
            // 使用 Application 容器获取 Router 并执行请求
            $app = \Framework\Foundation\Application::getInstance();
            if (!$app) {
                throw new \RuntimeException('Application not initialized');
            }
            $router = $app->make(\Framework\Routing\Router::class);

            // 创建一个模拟的 GET 请求
            $path = parse_url($url, PHP_URL_PATH) ?? $url;
            $subRequest = Request::create($path, 'GET');

            // 使用 Router dispatch 获取响应
            $response = $router->dispatch($subRequest);

            if (!$response instanceof Response) {
                return Response::json(['error' => 'Invalid response'], 500);
            }

            // 解析 HTML 提取 fragments
            $html = $response->getSfResponse()->getContent() ?? '';
            $fragments = $this->extractNavigateFragments($html);
            $title = $this->extractTitle($html);

            return Response::json([
                'url' => $url,
                'title' => $title,
                'fragments' => $fragments,
            ]);
        } catch (\Throwable $e) {
            // 临时调试日志
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

        // 如果没有找到任何 fragment，返回整个 body 作为默认
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
        $stateInfo = \Framework\Component\LiveEventBus::getComponentState($componentId);
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

        // 查找是否已经存在该组件的更新，合并它
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
