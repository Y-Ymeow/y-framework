<?php

declare(strict_types=1);

namespace Framework\Testing;

use Framework\Component\Live\LiveComponent;
use Framework\Http\Request\Request;

trait InteractsWithLive
{
    public function live(string $componentClass, array $props = []): LiveTest
    {
        return new LiveTest($componentClass, $props);
    }
}

class LiveTest
{
    private string $componentClass;
    private array $props = [];
    private ?LiveComponent $instance = null;
    private ?array $state = null;
    private ?string $html = null;
    private ?array $lastResult = null;

    public function __construct(string $componentClass, array $props = [])
    {
        $this->componentClass = $componentClass;
        $this->props = $props;
        $this->boot();
    }

    private function boot(): void
    {
        if (!class_exists($this->componentClass)) {
            throw new \RuntimeException("Live component class '{$this->componentClass}' does not exist");
        }

        $this->instance = new ($this->componentClass)();

        foreach ($this->props as $key => $value) {
            $this->setProp($key, $value);
        }

        $this->html = (string) $this->instance->render();
        $this->state = json_decode($this->instance->serializeState(), true) ?? [];
    }

    private function setProp(string $key, mixed $value): void
    {
        if (!property_exists($this->instance, $key)) {
            throw new \RuntimeException(
                "Property '\${$key}' does not exist on {$this->componentClass}"
            );
        }

        $ref = new \ReflectionProperty($this->instance, $key);
        $ref->setAccessible(true);
        $ref->setValue($this->instance, $value);
    }

    private function getProp(string $key): mixed
    {
        $ref = new \ReflectionProperty($this->instance, $key);
        $ref->setAccessible(true);
        return $ref->getValue($this->instance);
    }

    public function set(string $prop, mixed $value): self
    {
        $this->setProp($prop, $value);
        $this->html = (string) $this->instance->render();
        $this->state = json_decode($this->instance->serializeState(), true) ?? [];
        return $this;
    }

    public function call(string $action, array $params = []): self
    {
        if (!method_exists($this->instance, $action)) {
            throw new \RuntimeException(
                "Method '{$action}' does not exist on {$this->componentClass}"
            );
        }

        $method = new \ReflectionMethod($this->instance, $action);
        $method->setAccessible(true);
        $method->invokeArgs($this->instance, $params);

        $this->html = (string) $this->instance->render();
        $this->state = json_decode($this->instance->serializeState(), true) ?? [];
        return $this;
    }

    public function assertSet(string $prop, mixed $expected): self
    {
        $actual = $this->getProp($prop);
        \PHPUnit\Framework\Assert::assertEquals(
            $expected,
            $actual,
            "Live property '\${$prop}' mismatch"
        );
        return $this;
    }

    public function assertCount(string $prop, int $expected): self
    {
        $value = $this->getProp($prop);
        \PHPUnit\Framework\Assert::assertCount(
            $expected,
            is_countable($value) ? $value : [],
            "Live property '\${$prop}' count mismatch"
        );
        return $this;
    }

    public function assertSee(string $text): self
    {
        \PHPUnit\Framework\Assert::assertStringContainsString(
            $text,
            $this->html ?? '',
            "Failed asserting that Live component renders text: '{$text}'"
        );
        return $this;
    }

    public function assertDontSee(string $text): self
    {
        \PHPUnit\Framework\Assert::assertStringNotContainsString(
            $text,
            $this->html ?? '',
            "Failed asserting that Live component does NOT render: '{$text}'"
        );
        return $this;
    }

    public function assertSeeHtml(string $html): self
    {
        \PHPUnit\Framework\Assert::assertStringContainsString(
            $html,
            $this->html ?? '',
            "Failed asserting that Live component contains HTML: '{$html}'"
        );
        return $this;
    }

    public function getHtml(): string
    {
        return $this->html ?? '';
    }

    public function getState(): array
    {
        return $this->state ?? [];
    }

    public function instance(): LiveComponent
    {
        return $this->instance;
    }
}
