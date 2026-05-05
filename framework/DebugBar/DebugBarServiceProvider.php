<?php

declare(strict_types=1);

namespace Framework\DebugBar;

use Framework\Foundation\ServiceProvider;

class DebugBarServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $debug = \Framework\Foundation\Application::isDebug();
        if (!$debug) {
            return;
        }

        $debugBar = DebugBar::getInstance();
        $this->app->instance(DebugBar::class, $debugBar);

        // 注入 debug-id 到 HTML 头部
        \Framework\View\Document\Document::addMeta('debug-id', $debugBar->getKey());

        // 手动注册组件到生命周期管理器
        \Framework\Lifecycle\LifecycleManager::getInstance()->registerComponent([
            'class' => \Framework\DebugBar\Components\DebugBarComponent::class,
            'name' => 'DebugBarComponent',
        ]);

        $listener = new DebugBarListener($debugBar);
        $listener->register();
    }
}
