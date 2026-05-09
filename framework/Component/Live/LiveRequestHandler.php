<?php

declare(strict_types=1);

namespace Framework\Component\Live;

use Framework\Foundation\Application;
use Framework\Foundation\AppEnvironment;
use Framework\Events\LiveActionEvent;
use Framework\Http\Middleware\VerifyCsrfToken;
use Framework\Http\Request\Request;
use Framework\Http\Response\Response;
use Framework\Http\Response\SseResponse;
use Framework\Http\Response\StreamResponse;
use Framework\Http\Session\Session;
use Framework\Http\Upload\Upload;
use Framework\Routing\Attribute\Route;
use Framework\Routing\Attribute\RouteGroup;
use Framework\View\Document\AssetRegistry;
use Framework\View\FragmentRegistry;
use Admin\Content\Media;

#[RouteGroup('/live', name: 'live')]
class LiveRequestHandler
{
    /**
     * Component class allowlist.
     *
     * When non-empty, only classes in this list (or classes whose namespace
     * is prefixed by an entry ending with a backslash) may be instantiated
     * via live endpoints.
     *
     * Configure via config('live.component_whitelist', []) or extend
     * the getComponentWhitelist() method.
     */
    private static array $componentWhitelist = [];

    // ─── Endpoints ──────────────────────────────

    /**
     * POST /live/action — full action dispatch
     */
    #[Route('/action', ['POST'], name: 'live.action', middleware: [VerifyCsrfToken::class])]
    public function handleAction(Request $request): Response
    {
        $params = $this->extractParams($request);
        if ($params === null) {
            return Response::json(['success' => false, 'error' => 'Missing component or action'], 400);
        }

        $error = $this->guardComponentClass($params['componentClass']);
        if ($error) {
            return $error;
        }

        try {
            $component = $this->resolveComponent(
                $params['componentClass'],
                $request->input('_component_id', ''),
            );

            $this->registerPeerComponents($request);
            $this->injectParentComponent($component, $request);

            if (!empty($params['state'])) {
                $component->deserializeState($params['state']);
                $component->fillPublicProperties($params['publicData']);
            }

            $component->_invokeAction();

            $before = $component->getDataForFrontend();
            $result = $component->callAction($params['action'], $params['params']);

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

            $newState = $component->serializeState();

            $response = [
                'success' => true,
                'component' => $params['componentClass'],
                'action' => $params['action'],
                'state' => $newState,
                'patches' => $this->diffPatches($before, $after),
                'domPatches' => [],
                'fragments' => [],
                'operations' => [],
                'componentUpdates' => [],
                'events' => $component->getEmittedEvents(),
            ];

            $this->collectEmittedEvents($response, $component);
            $this->collectManualUpdates($response, $component);
            $this->collectFragments($response, $component);
            $this->collectActionResult($response, $result);
            $this->collectComponentOperations($response, $component);

            $event = new LiveActionEvent($response, $component, app()->make(Request::class));
            \Framework\Events\Hook::getInstance()->dispatch($event);
            $response = $event->getResponse();
            $this->appendRequestedScripts($response);

            return Response::json($response);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * POST /live/state — lightweight state property update
     *
     * No action invocation. Purely deserialize → validate → merge → onUpdate → serialize.
     * Returns a minimal response with patches and new state only.
     */
    #[Route('/state', ['POST'], name: 'live.state', middleware: [VerifyCsrfToken::class])]
    public function handleStateUpdate(Request $request): Response
    {
        $componentClass = $request->header('x-live-component')
            ?? $request->input('_component');

        if (empty($componentClass)) {
            return Response::json(['success' => false, 'error' => 'Missing component class'], 400);
        }

        $error = $this->guardComponentClass($componentClass);
        if ($error) {
            return $error;
        }

        try {
            $component = $this->resolveComponent(
                $componentClass,
                $request->input('_component_id', ''),
            );

            $this->injectParentComponent($component, $request);

            $state = $request->input('_state', '');
            $publicData = $request->input('_data', []);
            if (!is_array($publicData)) {
                $publicData = [];
            }

            $before = $component->getDataForFrontend();

            if (!empty($state)) {
                $component->deserializeState($state);
                $component->fillPublicProperties($publicData);
            }

            // Fire onUpdate lifecycle hook after state merge
            $component->onUpdate();

            $after = $component->getDataForFrontend();

            $newState = $component->serializeState();

            return Response::json([
                'success' => true,
                'component' => $componentClass,
                'state' => $newState,
                'patches' => $this->diffPatches($before, $after),
                'events' => $component->getEmittedEvents(),
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * POST /live/update — backward-compatible alias for /live/action
     *
     * @deprecated Use /live/action for action calls, /live/state for state updates.
     * Will be removed in a future version.
     */
    #[Route('/update', ['POST'], name: 'live.update', middleware: [VerifyCsrfToken::class])]
    public function handle(Request $request): Response
    {
        return $this->handleAction($request);
    }

    /**
     * POST /live/event — event dispatch to parent component listener
     *
     * Lightweight endpoint for triggering #[LiveListener] methods on parent components.
     * Used by child components to notify parent components via $emit().
     */
    #[Route('/event', ['POST'], name: 'live.event', middleware: [VerifyCsrfToken::class])]
    public function handleEvent(Request $request): Response
    {
        $componentClass = $request->header('x-live-component')
            ?? $request->input('_component');

        if (empty($componentClass)) {
            return Response::json(['success' => false, 'error' => 'Missing component class'], 400);
        }

        $error = $this->guardComponentClass($componentClass);
        if ($error) {
            return $error;
        }

        try {
            $component = $this->resolveComponent(
                $componentClass,
                $request->input('_component_id', ''),
            );

            $state = $request->input('_state', '');
            $publicData = $request->input('_data', []);
            if (!is_array($publicData)) {
                $publicData = [];
            }

            if (!empty($state)) {
                $component->deserializeState($state);
                $component->fillPublicProperties($publicData);
            }

            $eventName = $request->input('_event');
            $eventParams = $request->input('_params', []);

            $handlerMethod = $this->findEventHandler($component, $eventName);
            if (!$handlerMethod) {
                return Response::json(['success' => false, 'error' => "No listener for event: {$eventName}"], 404);
            }

            $before = $component->getDataForFrontend();
            $component->$handlerMethod($eventParams);
            $after = $component->getDataForFrontend();

            $newState = $component->serializeState();

            return Response::json([
                'success' => true,
                'component' => $componentClass,
                'event' => $eventName,
                'state' => $newState,
                'patches' => $this->diffPatches($before, $after),
            ]);
        } catch (\Throwable $e) {
            return $this->errorResponse($e);
        }
    }

    private function findEventHandler(AbstractLiveComponent $component, string $eventName): ?string
    {
        $ref = new \ReflectionClass($component);
        foreach ($ref->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $attrs = $method->getAttributes(\Framework\Component\Live\Attribute\LiveListener::class);
            foreach ($attrs as $attr) {
                $listener = $attr->newInstance();
                if ($listener->event === $eventName) {
                    return $method->getName();
                }
            }
        }
        return null;
    }

    private function diffPatches(array $before, array $after): array
    {
        $patches = [];

        foreach ($after as $key => $value) {
            if (!array_key_exists($key, $before) || $before[$key] !== $value) {
                $patches[$key] = $value;
            }
        }

        return $patches;
    }

    #[Route('/stream', ['POST'], name: 'live.stream', middleware: [VerifyCsrfToken::class])]
    public function stream(Request $request): Response
    {
        $error = $this->guardCsrf($request);
        if ($error) return $error;

        $params = $this->extractParams($request);
        if ($params === null) {
            return Response::json(['success' => false, 'error' => 'Missing component or action'], 400);
        }

        $error = $this->guardComponentClass($params['componentClass']);
        if ($error) return $error;

        try {
            $this->registerPeerComponents($request);

            $component = $this->resolveComponent(
                $params['componentClass'],
                $request->input('_component_id', ''),
            );

            $component->_invokeAction($params['params']);

            if (!empty($params['state'])) {
                $component->deserializeState($params['state']);
            }

            $component->fillPublicProperties($params['publicData']);

            $result = $component->callAction($params['action'], $params['params']);

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
            return $this->errorResponse($e);
        }
    }

    #[Route('/navigate', ['POST'], name: 'live.navigate', middleware: [VerifyCsrfToken::class])]
    public function navigate(Request $request): Response
    {
        logger()->info('navigate', $request->all());
        $error = $this->guardCsrf($request);
        if ($error) return $error;

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
            $sources = $this->extractSources($html);

            return Response::json([
                'url' => $url,
                'title' => $title,
                'fragments' => $fragments,
                'sources' => $sources,
            ]);
        } catch (\Throwable $e) {
            $this->logNavigateError($e);

            return Response::json([
                'error' => $e->getMessage(),
                'trace' => Application::isDebug() ? $e->getTraceAsString() : null,
            ], 500);
        }
    }

    #[Route('/intl', ['POST'], name: 'live.intl', middleware: [VerifyCsrfToken::class])]
    public function intl(Request $request): Response
    {
        $error = $this->guardCsrf($request);
        if ($error) return $error;

        $keys = $request->input('keys', []);
        if (!is_array($keys)) {
            $keys = [];
        }

        $locale = $request->input('locale', '');
        if (!empty($locale)) {
            \Framework\Intl\Translator::setLocale($locale);
            setcookie('locale', $locale, [
                'expires' => time() + 60 * 60 * 24 * 365,
                'path' => '/',
                'httponly' => false,
                'samesite' => 'Lax',
            ]);
        }

        $translations = \Framework\Intl\Translator::getMany($keys);

        return Response::json([
            'success' => true,
            'locale' => \Framework\Intl\Translator::getLocale(),
            'translations' => $translations,
        ]);
    }

    #[Route('/upload', ['POST'], name: 'live.upload', middleware: [VerifyCsrfToken::class])]
    public function upload(Request $request): Response
    {
        $error = $this->guardCsrf($request);
        if ($error) return $error;

        $file = Upload::from('file');
        if (!$file) {
            return Response::json(['success' => false, 'error' => 'No file uploaded'], 400);
        }

        if (!$file->isValid()) {
            return Response::json(['success' => false, 'error' => $file->getErrorMessage()], 400);
        }

        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml', 'video/mp4', 'application/pdf'];
        $errors = $file->allowedMimes($allowedMimes)->maxSize(10 * 1024 * 1024)->validate();
        if (!empty($errors)) {
            return Response::json(['success' => false, 'error' => implode(', ', $errors)], 400);
        }

        $datePath = date('Y/m');
        $directory = paths()->uploads() . '/' . $datePath;
        $storedName = $file->store($directory);

        $url = '/media/' . $datePath . '/' . $storedName;

        try {
            $media = Media::create([
                'disk'      => 'uploads',
                'path'      => $datePath . '/' . $storedName,
                'filename'  => $file->getName(),
                'extension' => (string) $file->getExtension(),
                'mime_type' => $file->getMime(),
                'size'      => $file->getSize(),
                'alt'       => '',
                'title'     => pathinfo((string) $file->getName(), PATHINFO_FILENAME),
            ]);
        } catch (\Throwable $e) {
            $this->logUploadError($e);
        }

        return Response::json([
            'success' => true,
            'url' => $url,
            'name' => $file->getName(),
            'size' => $file->getSize(),
            'mime' => $file->getMime(),
            'id' => isset($media) ? $media->id : null,
        ]);
    }

    // ─── 核心方法 ───────────────────────────────

    private function guardCsrf(Request $request): ?Response
    {
        if (AppEnvironment::isWasm()) {
            return null;
        }

        $session = app()->make(Session::class);
        $csrfToken = $request->header('x-csrf-token')
            ?? $request->input('_token', '');

        if (!$session->verifyToken((string) $csrfToken)) {
            return Response::json(['success' => false, 'error' => 'Invalid CSRF token'], 419);
        }

        return null;
    }

    private function extractParams(Request $request): ?array
    {
        $componentClass = $request->header('x-live-component')
            ?? $request->input('_component');

        $action = $request->header('x-live-action')
            ?? $request->input('_action');

        $state = $request->input('_state', '');
        $publicData = $request->input('_data', []);
        $params = $request->input('_params', []);

        if (!is_array($publicData)) {
            $publicData = [];
        }

        if (!is_array($params)) {
            $params = [];
        }

        if (empty($componentClass) || empty($action)) {
            return null;
        }

        return compact('componentClass', 'action', 'state', 'publicData', 'params');
    }

    private function guardComponentClass(string $class): ?Response
    {
        if (!class_exists($class)) {
            return Response::json(['success' => false, 'error' => "Component [{$class}] not found"], 404);
        }

        // Ensure the class is a LiveComponent or EmbeddedLiveComponent (both extend AbstractLiveComponent)
        if (!is_subclass_of($class, AbstractLiveComponent::class)) {
            return Response::json(['success' => false, 'error' => "Class [{$class}] is not a valid Live component"], 403);
        }

        // Check allowlist if configured
        $whitelist = static::getComponentWhitelist();
        if (!empty($whitelist) && !$this->isInWhitelist($class, $whitelist)) {
            return Response::json([
                'success' => false,
                'error' => "Component [{$class}] is not on the allowed components list",
            ], 403);
        }

        return null;
    }

    /**
     * Inject a parent LiveComponent into an EmbeddedLiveComponent child.
     *
     * Walks up the component tree based on the _parent_id provided in the payload.
     * If the parent itself is an Embedded component, its parent will also be
     * hydrated if metadata is available, enabling multi-level context access.
     */
    private function injectParentComponent(AbstractLiveComponent $component, Request $request): void
    {
        if (!($component instanceof EmbeddedLiveComponent)) {
            return;
        }

        $parentId = $request->input('_parent_id');
        if (empty($parentId)) {
            return;
        }

        $parent = $this->resolveComponentFromPayload($parentId, $request);
        if ($parent && $parent instanceof LiveComponent) {
            $component->setParent($parent);
        }
    }

    /**
     * Resolve a specific component from payload, checking both peer components and main target.
     */
    private function resolveComponentFromPayload(string $id, Request $request): ?AbstractLiveComponent
    {
        $components = $request->input('_components', []);
        foreach ($components as $compData) {
            if (($compData['id'] ?? '') === $id && !empty($compData['class']) && !empty($compData['state'])) {
                try {
                    /** @var AbstractLiveComponent $instance */
                    $instance = app()->make($compData['class']);
                    $instance->named($id);
                    $instance->deserializeState($compData['state']);
                    return $instance;
                } catch (\Throwable $e) {
                    if (Application::isDebug()) {
                        logger()->error("Failed to resolve component [{$id}] from payload: " . $e->getMessage());
                    }
                }
            }
        }
        return null;
    }

    /**
     * Get the component class allowlist.
     *
     * Returns entries from config('live.component_whitelist', []) or
     * the statically set list via setComponentWhitelist().
     *
     * Each entry can be a full class name or a namespace prefix (ending
     * with a backslash) that matches all classes under that namespace.
     */
    public static function getComponentWhitelist(): array
    {
        if (!empty(static::$componentWhitelist)) {
            return static::$componentWhitelist;
        }

        $config = config('live.component_whitelist', null);
        if (is_array($config)) {
            static::$componentWhitelist = $config;
        }

        return static::$componentWhitelist;
    }

    /**
     * Set the component class allowlist statically (e.g. for tests).
     */
    public static function setComponentWhitelist(array $whitelist): void
    {
        static::$componentWhitelist = $whitelist;
    }

    /**
     * Check if a class is in the whitelist.
     */
    private function isInWhitelist(string $class, array $whitelist): bool
    {
        foreach ($whitelist as $entry) {
            if ($class === $entry) {
                return true;
            }
            // Namespace prefix match (ends with backslash)
            if (str_ends_with($entry, '\\') && str_starts_with($class, $entry)) {
                return true;
            }
        }
        return false;
    }

    private function registerPeerComponents(Request $request): void
    {
        LiveEventBus::reset();

        $components = $request->input('_components');
        if (!is_array($components)) {
            return;
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
    }

    private function resolveComponent(string $class, string $componentId): AbstractLiveComponent
    {
        $component = app()->make($class);

        if (!($component instanceof AbstractLiveComponent)) {
            throw new \Framework\Exception\ComponentNotFoundException($class);
        }

        if (!empty($componentId)) {
            $component->named($componentId);
        }

        return $component;
    }

    private function collectEmittedEvents(array &$response, AbstractLiveComponent $component): void
    {
        $emittedEvents = $component->getEmittedEvents();
        foreach ($emittedEvents as $emittedEvent) {
            $targetId = $emittedEvent['targetId'] ?? null;

            if ($targetId !== null) {
                // 定向事件：直接发送到指定组件
                $listener = LiveEventBus::findListenerForComponent($targetId, $emittedEvent['event']);
                if ($listener) {
                    $this->processComponentUpdate($listener['componentId'], $response, function ($comp) use ($listener, $emittedEvent) {
                        $handler = $listener['handler'];
                        if (method_exists($comp, $handler)) {
                            $comp->$handler($emittedEvent['params']);
                        }
                    });
                }
            } else {
                // 广播事件：查找所有监听器（排除自己）
                $listeners = LiveEventBus::findListenersForEvent(
                    $emittedEvent['event'],
                    $component->getComponentId()
                );

                foreach ($listeners as $listener) {
                    $this->processComponentUpdate($listener['componentId'], $response, function ($comp) use ($listener, $emittedEvent) {
                        $handler = $listener['handler'];
                        if (method_exists($comp, $handler)) {
                            $comp->$handler($emittedEvent['params']);
                        }
                    });
                }
            }
        }
    }

    private function collectManualUpdates(array &$response, AbstractLiveComponent $component): void
    {
        $manualUpdates = $component->getManualUpdates();
        foreach ($manualUpdates as $targetId => $patchData) {
            $this->processComponentUpdate($targetId, $response, function ($comp) use ($patchData) {
                $ref = new \ReflectionClass($comp);
                foreach ($patchData as $key => $val) {
                    if ($ref->hasProperty($key)) {
                        $prop = $ref->getProperty($key);
                        $prop->setValue($comp, $val);
                    }
                }
            });
        }
    }

    private function collectFragments(array &$response, AbstractLiveComponent $component): void
    {
        $refreshTargets = $component->getRefreshFragments();
        if (empty($refreshTargets)) {
            return;
        }

        FragmentRegistry::getInstance()->reset();
        FragmentRegistry::getInstance()->setTargets($refreshTargets);
        $component->render();

        foreach (FragmentRegistry::getInstance()->getFragments() as $name => $data) {
            $response['fragments'][] = [
                'name' => $name,
                'html' => $data['element']->render(),
                'mode' => $data['mode'],
            ];
        }
    }

    private function collectActionResult(array &$response, mixed $result): void
    {
        if ($result instanceof LiveResponse) {
            $lr = $result->toArray();
            if (!empty($lr['domPatches'])) {
                $response['domPatches'] = $lr['domPatches'];
            }

            if (!empty($lr['fragments'])) {
                $response['fragments'] = array_merge($response['fragments'], $lr['fragments']);
            }

            if (!empty($lr['operations'])) {
                $response['operations'] = array_merge($response['operations'], $lr['operations']);
            }
        } elseif (is_array($result) && isset($result['operations'])) {
            $response['operations'] = array_merge($response['operations'], $result['operations']);
        }
    }

    private function collectComponentOperations(array &$response, AbstractLiveComponent $component): void
    {
        $componentOps = $component->getOperations();

        if (!empty($componentOps)) {
            $response['operations'] = array_merge($response['operations'], $componentOps);
        }
    }

    private function appendRequestedScripts(array &$response): void
    {
        $registry = AssetRegistry::getInstance();
        $ids = $registry->getRequestedScriptIds();

        if (empty($ids)) {
            return;
        }

        $src = $registry->buildScriptUrl($ids);
        if ($src === '') {
            return;
        }

        $response['operations'][] = [
            'op' => 'loadScript',
            'src' => $src,
            'id' => 'live:' . md5(implode(',', $ids)),
        ];
    }

    private function processComponentUpdate(string $componentId, array &$response, callable $callback): void
    {
        $stateInfo = LiveEventBus::getComponentState($componentId);
        if (!$stateInfo) {
            return;
        }

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
        $refreshTargets = $comp->getRefreshFragments();
        if (!empty($refreshTargets)) {
            FragmentRegistry::getInstance()->reset();
            FragmentRegistry::getInstance()->setTargets($refreshTargets);
            $comp->render();
            foreach (FragmentRegistry::getInstance()->getFragments() as $name => $data) {
                $fragments[] = [
                    'name' => $name,
                    'html' => $data['element']->render(),
                    'mode' => $data['mode'],
                ];
            }
        }

        $ops = $comp->getOperations();
        if (!empty($ops)) {
            $response['operations'] = array_merge($response['operations'], $ops);
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

    private function extractSources(string $html): array
    {
        $dom = \Framework\Support\Dom::load($html);
        $css = [];
        $js = [];

        $links = $dom->query('//head/link[@rel="stylesheet"]');
        foreach ($links as $link) {
            if ($link instanceof \DOMElement) {
                $href = $link->getAttribute('href');
                if ($href) {
                    $id = $link->getAttribute('id') ?: null;
                    $css[] = ['href' => $href, 'id' => $id];
                }
            }
        }

        $scripts = $dom->query('//script[@src]');
        foreach ($scripts as $script) {
            if ($script instanceof \DOMElement) {
                $src = $script->getAttribute('src');
                if ($src) {
                    $id = $script->getAttribute('id') ?: null;
                    $isModule = $script->getAttribute('type') === 'module';
                    $js[] = ['src' => $src, 'id' => $id, 'module' => $isModule];
                }
            }
        }

        return ['css' => $css, 'js' => $js];
    }

    private function logNavigateError(\Throwable $e): void
    {
        $logFile = __DIR__ . '/../../../storage/logs/navigate-error.log';
        @mkdir(dirname($logFile), 0755, true);
        @file_put_contents($logFile, date('Y-m-d H:i:s') . ' - ' . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n\n", FILE_APPEND);
    }

    private function logUploadError(\Throwable $e): void
    {
        $logFile = __DIR__ . '/../../../storage/logs/live-upload-error.log';
        @mkdir(dirname($logFile), 0755, true);
        @file_put_contents($logFile, date('Y-m-d H:i:s') . ' - ' . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n\n", FILE_APPEND);
    }

    private function errorResponse(\Throwable $e): Response
    {
        return Response::json([
            'success' => false,
            'error' => $e->getMessage(),
            'trace' => Application::isDebug() ? $e->getTraceAsString() : null,
        ], 500);
    }
}
