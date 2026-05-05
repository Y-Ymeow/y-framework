<?php

declare(strict_types=1);

namespace Framework\Lifecycle;

class ComponentCollector implements CollectorInterface
{
    private array $components = [];
    private array $tagged = [];

    public function collect(array $items): void
    {
        foreach ($items as $item) {
            $this->addComponent($item);
        }
    }

    public function addComponent(array $component): void
    {
        $defaults = [
            'class' => '',
            'name' => '',
            'group' => 'default',
            'tags' => [],
            'options' => [],
        ];
        $component = array_merge($defaults, $component);
        $component['name'] = $component['name'] ?: $component['class'];
        $this->components[] = $component;

        foreach ($component['tags'] as $tag) {
            $this->tagged[$tag][] = $component;
        }
    }

    public function getCollected(): array
    {
        return $this->components;
    }

    public function getByName(string $name): ?array
    {
        foreach ($this->components as $component) {
            if ($component['name'] === $name) {
                return $component;
            }
        }
        return null;
    }

    public function getByGroup(string $group): array
    {
        return array_filter($this->components, fn($c) => $c['group'] === $group);
    }

    public function getByTag(string $tag): array
    {
        return $this->tagged[$tag] ?? [];
    }

    public function clear(): void
    {
        $this->components = [];
        $this->tagged = [];
    }

    public function count(): int
    {
        return count($this->components);
    }
}
