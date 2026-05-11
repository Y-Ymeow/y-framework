<?php

declare(strict_types=1);

namespace Framework\Plugin;

use Framework\Events\Hook;
use Framework\Events\PluginBootingEvent;
use Framework\Events\PluginBootedEvent;

class PluginManager
{
    protected string $pluginsPath;

    protected array $plugins = [];

    protected array $pluginInstances = [];

    public function __construct(?string $pluginsPath = null)
    {
        $this->pluginsPath = $pluginsPath ?? dirname(__DIR__, 2) . '/plugins';
    }

    public function scan(): array
    {
        $this->plugins = [];

        if (!is_dir($this->pluginsPath)) {
            return $this->plugins;
        }

        $items = scandir($this->pluginsPath);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $pluginDir = $this->pluginsPath . '/' . $item;
            $metaFile = $pluginDir . '/plugin.json';

            if (!is_dir($pluginDir) || !file_exists($metaFile)) {
                continue;
            }

            $meta = json_decode(file_get_contents($metaFile), true);

            if (!$meta || empty($meta['name'])) {
                continue;
            }

            $this->plugins[$meta['name']] = array_merge([
                'title' => $meta['name'],
                'description' => '',
                'version' => '1.0.0',
                'class' => $this->detectPluginClass($pluginDir, $meta['name']),
                'path' => $pluginDir,
            ], $meta);
        }

        return $this->plugins;
    }

    public function getPlugins(): array
    {
        return $this->plugins;
    }

    public function getPlugin(string $name): ?array
    {
        return $this->plugins[$name] ?? null;
    }

    public function boot(array $enabledNames): void
    {
        $this->scan();

        foreach ($enabledNames as $name) {
            if (!isset($this->plugins[$name])) {
                continue;
            }

            $meta = $this->plugins[$name];

            try {
                $instance = $this->loadPlugin($meta);

                if (!$instance) {
                    continue;
                }

                Hook::getInstance()->dispatch(new PluginBootingEvent($instance));
                $instance->boot();
                Hook::getInstance()->dispatch(new PluginBootedEvent($instance));

                $this->pluginInstances[$name] = $instance;
            } catch (\Throwable $e) {
                logger()->error("Plugin [{$name}] boot error: " . $e->getMessage());
            }
        }
    }

    public function getInstance(string $name): ?PluginInterface
    {
        return $this->pluginInstances[$name] ?? null;
    }

    public function getInstances(): array
    {
        return $this->pluginInstances;
    }

    protected function loadPlugin(array $meta): ?PluginInterface
    {
        $pluginFile = $meta['path'] . '/Plugin.php';

        if (!file_exists($pluginFile)) {
            return null;
        }

        require_once $pluginFile;

        $class = $meta['class'] ?? '';

        if (!$class || !class_exists($class)) {
            return null;
        }

        $instance = new $class();

        if (!$instance instanceof PluginInterface) {
            return null;
        }

        return $instance;
    }

    protected function detectPluginClass(string $pluginDir, string $name): string
    {
        $pluginFile = $pluginDir . '/Plugin.php';

        if (!file_exists($pluginFile)) {
            return '';
        }

        $tokens = token_get_all(file_get_contents($pluginFile));

        $namespace = '';
        $class = '';

        for ($i = 0; $i < count($tokens); $i++) {
            if ($tokens[$i][0] === T_NAMESPACE) {
                $namespace = '';
                for ($j = $i + 1; $j < count($tokens); $j++) {
                    if ($tokens[$j] === ';' || $tokens[$j] === '{') {
                        break;
                    }
                    if (is_array($tokens[$j])) {
                        $namespace .= $tokens[$j][1];
                    }
                }
            }

            if ($tokens[$i][0] === T_CLASS) {
                for ($j = $i + 1; $j < count($tokens); $j++) {
                    if ($tokens[$j][0] === T_STRING) {
                        $class = $tokens[$j][1];
                        break;
                    }
                }
            }
        }

        if ($namespace && $class) {
            return $namespace . '\\' . $class;
        }

        return $class ?: '';
    }
}

