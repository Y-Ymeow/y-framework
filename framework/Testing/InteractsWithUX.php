<?php

declare(strict_types=1);

namespace Framework\Testing;

use Framework\UX\UXComponent;
use DOMDocument;
use Tests\Support\DOMAssert;

trait InteractsWithUX
{
    public function ux(string $componentClass): UXTest
    {
        return new UXTest($componentClass);
    }
}

class UXTest
{
    private string $componentClass;
    private ?UXComponent $instance = null;
    private string $html = '';

    public function __construct(string $componentClass)
    {
        if (!class_exists($componentClass)) {
            throw new \RuntimeException("UX component class '{$componentClass}' does not exist");
        }

        $this->componentClass = $componentClass;
        $this->instance = new ($componentClass)();
        $this->html = (string) $this->instance->render();
    }

    public function assertHasClass(string $class): self
    {
        \PHPUnit\Framework\Assert::assertMatchesRegularExpression(
            '/\b' . preg_quote($class, '/') . '\b/',
            $this->html,
            "Failed asserting that UX component has class '{$class}'. HTML:\n" . substr($this->html, 0, 300)
        );
        return $this;
    }

    public function assertNotHasClass(string $class): self
    {
        \PHPUnit\Framework\Assert::assertDoesNotMatchRegularExpression(
            '/\b' . preg_quote($class, '/') . '\b/',
            $this->html
        );
        return $this;
    }

    public function assertContains(string $needle): self
    {
        \PHPUnit\Framework\Assert::assertStringContainsString(
            $needle,
            $this->html,
            "Failed asserting that UX component contains '{$needle}'"
        );
        return $this;
    }

    public function assertNotContains(string $needle): self
    {
        \PHPUnit\Framework\Assert::assertStringNotContainsString(
            $needle,
            $this->html
        );
        return $this;
    }

    public function assertSeeText(string $text): self
    {
        $dom = DOMAssert::parseHtml($this->html);
        \PHPUnit\Framework\Assert::assertStringContainsString(
            $text,
            $dom->textContent ?? ''
        );
        return $this;
    }

    public function assertSeeInElement(string $selector, string $text): self
    {
        $dom = DOMAssert::parseHtml($this->html);
        $xpath = DOMAssert::xpath($dom);
        $result = $xpath->query(DOMAssert::selectorToXpath($selector));

        if (!$result || $result->length === 0) {
            \PHPUnit\Framework\Assert::fail("Selector '{$selector}' not found in UX component");
        }

        $found = false;
        foreach ($result as $node) {
            if (str_contains($node->textContent ?? '', $text)) {
                $found = true;
                break;
            }
        }

        \PHPUnit\Framework\Assert::assertTrue(
            $found,
            "Failed to find text '{$text}' in element matching '{$selector}'"
        );
        return $this;
    }

    public function assertDataAttribute(string $key, string $value): self
    {
        \PHPUnit\Framework\Assert::assertStringContainsString(
            "data-{$key}=\"{$value}\"",
            $this->html
        );
        return $this;
    }

    public function assertAttribute(string $attr, string $value): self
    {
        \PHPUnit\Framework\Assert::assertStringContainsString(
            "{$attr}=\"{$value}\"",
            $this->html
        );
        return $this;
    }

    public function getHtml(): string
    {
        return $this->html;
    }

    public function getDom(): DOMDocument
    {
        return DOMAssert::parseHtml($this->html);
    }

    public function instance(): UXComponent
    {
        return $this->instance;
    }
}
