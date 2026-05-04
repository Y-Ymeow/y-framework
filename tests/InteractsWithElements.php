<?php

declare(strict_types=1);

namespace Tests;

use DOMDocument;
use DOMNodeList;
use DOMXPath;
use Framework\View\Base\Element;
use Tests\Support\DOMAssert;

trait InteractsWithElements
{
    protected function elementHtml(Element $el): string
    {
        return (string) $el;
    }

    protected function elementDOM(Element $el): DOMDocument
    {
        return DOMAssert::parseHtml($this->elementHtml($el));
    }

    protected function elementQuery(Element $el, string $xpath): ?DOMNodeList
    {
        return DOMAssert::xpath($this->elementDOM($el))->query($xpath);
    }

    public function assertElementTextContains(Element $el, string $text): void
    {
        $html = $this->elementHtml($el);
        $this->assertStringContainsString($text, $html);
    }

    public function assertElementTextNotContains(Element $el, string $text): void
    {
        $html = $this->elementHtml($el);
        $this->assertStringNotContainsString($text, $html);
    }

    public function assertElementHasAttribute(Element $el, string $attr, ?string $value = null): void
    {
        $html = $this->elementHtml($el);
        if ($value !== null) {
            $this->assertStringContainsString("{$attr}=\"{$value}\"", $html);
        } else {
            $this->assertStringContainsString($attr . '=', $html);
        }
    }

    public function assertElementHasClass(Element $el, string $class): void
    {
        $html = $this->elementHtml($el);
        $this->assertMatchesRegularExpression('/\b' . preg_quote($class, '/') . '\b/', $html);
    }

    public function assertElementNotHasClass(Element $el, string $class): void
    {
        $html = $this->elementHtml($el);
        $this->assertDoesNotMatchRegularExpression('/\b' . preg_quote($class, '/') . '\b/', $html);
    }

    public function assertElementChildCount(Element $el, int $expected): void
    {
        $html = $this->elementHtml($el);
        // Count direct child elements by counting opening tags at root level
        $dom = DOMAssert::parseHtml($html);
        $root = $dom->documentElement;
        if (!$root) {
            $this->assertEquals(0, $expected);
            return;
        }
        $childCount = 0;
        foreach ($root->childNodes as $node) {
            if ($node->nodeType === XML_ELEMENT_NODE) {
                $childCount++;
            }
        }
        $this->assertEquals($expected, $childCount);
    }

    public function assertElementDataAttributes(Element $el, array $attrs): void
    {
        $html = $this->elementHtml($el);
        foreach ($attrs as $key => $value) {
            $this->assertStringContainsString("data-{$key}=\"{$value}\"", $html);
        }
    }
}
