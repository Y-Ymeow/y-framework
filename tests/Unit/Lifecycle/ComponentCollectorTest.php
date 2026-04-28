<?php

declare(strict_types=1);

namespace Tests\Unit\Lifecycle;

use Framework\Lifecycle\ComponentCollector;

class ComponentCollectorTest extends \PHPUnit\Framework\TestCase
{
    private ComponentCollector $collector;

    protected function setUp(): void
    {
        $this->collector = new ComponentCollector();
    }

    public function test_collect_components(): void
    {
        $components = [
            ['class' => 'App\Components\Header', 'name' => 'header'],
            ['class' => 'App\Components\Footer', 'name' => 'footer'],
        ];

        $this->collector->collect($components);
        $this->assertEquals(2, $this->collector->count());
    }

    public function test_add_component(): void
    {
        $this->collector->addComponent([
            'class' => 'App\Components\Button',
            'name' => 'button',
        ]);

        $this->assertEquals(1, $this->collector->count());
    }

    public function test_get_by_name(): void
    {
        $this->collector->addComponent([
            'class' => 'App\Components\Button',
            'name' => 'button',
        ]);

        $component = $this->collector->getByName('button');
        $this->assertNotNull($component);
        $this->assertEquals('App\Components\Button', $component['class']);
    }

    public function test_get_by_group(): void
    {
        $this->collector->addComponent([
            'class' => 'App\Components\Button',
            'name' => 'button',
            'group' => 'ui',
        ]);

        $this->collector->addComponent([
            'class' => 'App\Components\Input',
            'name' => 'input',
            'group' => 'forms',
        ]);

        $uiComponents = $this->collector->getByGroup('ui');
        $this->assertCount(1, $uiComponents);
    }

    public function test_get_by_tag(): void
    {
        $this->collector->addComponent([
            'class' => 'App\Components\Button',
            'name' => 'button',
            'tags' => ['ui', 'interactive'],
        ]);

        $this->collector->addComponent([
            'class' => 'App\Components\Input',
            'name' => 'input',
            'tags' => ['forms', 'interactive'],
        ]);

        $interactiveComponents = $this->collector->getByTag('interactive');
        $this->assertCount(2, $interactiveComponents);
    }

    public function test_clear(): void
    {
        $this->collector->addComponent(['class' => 'Test', 'name' => 'test']);
        $this->collector->clear();
        $this->assertEquals(0, $this->collector->count());
    }

    public function test_default_values(): void
    {
        $this->collector->addComponent(['class' => 'Test']);
        $collected = $this->collector->getCollected();

        $this->assertArrayHasKey('name', $collected[0]);
        $this->assertArrayHasKey('group', $collected[0]);
        $this->assertArrayHasKey('tags', $collected[0]);
        $this->assertEquals('Test', $collected[0]['name']);
        $this->assertEquals('default', $collected[0]['group']);
    }
}
