<?php

declare(strict_types=1);

namespace Tests;

use Framework\Component\LiveComponent;
use Framework\Http\Request;
use Framework\Component\LiveComponentResolver;
use Framework\Http\Session;

trait InteractsWithLiveComponents
{
    /**
     * 模拟调用 Live Component 的 Action
     */
    protected function liveCall(string $componentClass, string $action, array $state = [], array $params = [])
    {
        /** @var LiveComponent $component */
        $component = new $componentClass();
        
        // 准备初始状态
        $ref = new \ReflectionClass($component);
        foreach ($state as $key => $value) {
            if ($ref->hasProperty($key)) {
                $prop = $ref->getProperty($key);
                $prop->setAccessible(true);
                $prop->setValue($component, $value);
            }
        }

        $serializedState = $component->serializeState();

        // 模拟请求
        $request = Request::create('/live', 'POST', [
            '_component' => $componentClass,
            '_action' => $action,
            '_state' => $serializedState,
            '_params' => $params,
            '_token' => 'test-token', // 假 token
        ]);

        // Mock Session 以通过 CSRF 验证
        $session = $this->app->make(Session::class);
        $session->set('_token', 'test-token');

        $resolver = $this->app->make(LiveComponentResolver::class);
        $response = $resolver->handle($request);
        
        $decoded = json_decode($response->getSfResponse()->getContent(), true);
        if (!$decoded['success']) {
            echo "\nLive Action Error: " . ($decoded['error'] ?? 'Unknown') . "\n";
        }
        
        return $decoded;
    }
}
