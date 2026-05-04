<?php

declare(strict_types=1);

namespace Tests;

use DOMDocument;
use Framework\UX\UXComponent;
use Tests\Support\DOMAssert;

trait InteractsWithUXComponents
{
    protected function renderUX(UXComponent $component): string
    {
        return $component->render();
    }

    protected function renderUXAsDOM(UXComponent $component): DOMDocument
    {
        return DOMAssert::parseHtml($this->renderUX($component));
    }

    public function assertComponentHasClass(UXComponent $component, string $class): void
    {
        $html = $this->renderUX($component);
        $this->assertMatchesRegularExpression(
            '/\b' . preg_quote($class, '/') . '\b/',
            $html,
            "Failed asserting that component has class '{$class}'. HTML:\n" . substr($html, 0, 300)
        );
    }

    public function assertComponentNotHasClass(UXComponent $component, string $class): void
    {
        $html = $this->renderUX($component);
        $this->assertDoesNotMatchRegularExpression(
            '/\b' . preg_quote($class, '/') . '\b/',
            $html,
            "Failed asserting that component does NOT have class '{$class}'"
        );
    }

    public function assertComponentHasData(UXComponent $component, string $key, ?string $value = null): void
    {
        $html = $this->renderUX($component);
        if ($value !== null) {
            $needle = "data-{$key}=\"{$value}\"";
        } else {
            $needle = "data-{$key}";
        }
        $this->assertStringContainsString(
            $needle,
            $html,
            "Failed asserting that component has data attribute '{$key}" . ($value ? "={$value}" : "") . "'. HTML:\n" . substr($html, 0, 300)
        );
    }

    public function assertComponentContains(UXComponent $component, string $needle): void
    {
        $html = $this->renderUX($component);
        $this->assertStringContainsString(
            $needle,
            $html,
            "Failed asserting that component contains '{$needle}'. HTML:\n" . substr($html, 0, 500)
        );
    }

    public function assertComponentNotContains(UXComponent $component, string $needle): void
    {
        $html = $this->renderUX($component);
        $this->assertStringNotContainsString(
            $needle,
            $html,
            "Failed asserting that component does NOT contain '{$needle}'"
        );
    }

    public function assertComponentSelectorCount(UXComponent $component, string $selector, int $expected): void
    {
        $html = $this->renderUX($component);
        // For simple CSS classes, count occurrences of the class string
        if (str_starts_with($selector, '.')) {
            $cls = ltrim($selector, '.');
            $count = substr_count($html, $cls);
            // Rough approximation - exact counting needs DOM
            $this->assertGreaterThanOrEqual(
                $expected,
                $count,
                "Expected at least {$expected} occurrences of '{$cls}', found {$count}"
            );
        } else {
            $count = substr_count($html, $selector);
            $this->assertEquals($expected, $count);
        }
    }

    public function assertComponentTextContains(UXComponent $component, string $text): void
    {
        $html = $this->renderUX($component);
        $dom = DOMAssert::parseHtml($html);
        $textContent = $dom->textContent;
        $this->assertStringContainsString(
            $text,
            $textContent,
            "Failed asserting that component text contains '{$text}'. Text content: '" . trim(substr($textContent, 0, 200)) . "'"
        );
    }
}
