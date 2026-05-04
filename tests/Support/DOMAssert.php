<?php

declare(strict_types=1);

namespace Tests\Support;

use DOMDocument;
use DOMNodeList;
use DOMXPath;

class DOMAssert
{
    public static function parseHtml(string $html): DOMDocument
    {
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML(
            '<?xml encoding="UTF-8">' . mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'),
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        return $dom;
    }

    public static function xpath(DOMDocument $dom): DOMXPath
    {
        return new DOMXPath($dom);
    }

    public static function assertSelectorExists(string $html, string $selector): void
    {
        $dom = self::parseHtml($html);
        $xpath = self::selectorToXpath($selector);
        $result = self::xpath($dom)->query($xpath);
        if ($result === false || $result->length === 0) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                "Failed asserting that selector '{$selector}' exists in HTML.\nHTML: " . substr($html, 0, 500)
            );
        }
    }

    public static function assertSelectorCount(string $html, string $selector, int $expected): void
    {
        $dom = self::parseHtml($html);
        $xpath = self::selectorToXpath($selector);
        $result = self::xpath($dom)->query($xpath);
        if ($result === false) {
            throw new \PHPUnit\Framework\AssertionFailedError("Invalid XPath for selector: {$selector}");
        }
        $actual = $result->length;
        if ($actual !== $expected) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                "Failed asserting that selector '{$selector}' count is {$expected}. Actual: {$actual}"
            );
        }
    }

    public static function assertContainsText(string $html, string $text): void
    {
        $dom = self::parseHtml($html);
        $textContent = $dom->textContent;
        if (str_contains($textContent, $text) === false) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                "Failed asserting that HTML contains text '{$text}'.\nActual text content: " . trim(substr($textContent, 0, 300))
            );
        }
    }

    public static function assertNotContainsText(string $html, string $text): void
    {
        $dom = self::parseHtml($html);
        $textContent = $dom->textContent;
        if (str_contains($textContent, $text)) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                "Failed asserting that HTML does NOT contain text '{$text}'."
            );
        }
    }

    public static function assertAttributeEquals(string $html, string $selector, string $attr, string $value): void
    {
        $dom = self::parseHtml($html);
        $xpath = self::selectorToXpath($selector);
        $result = self::xpath($dom)->query($xpath);
        if ($result === false || $result->length === 0) {
            throw new \PHPUnit\Framework\AssertionFailedError("Selector '{$selector}' not found.");
        }
        $node = $result->item(0);
        if (!$node || !($node instanceof \DOMElement) || !$node->hasAttribute($attr)) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                "Element '{$selector}' does not have attribute '{$attr}'."
            );
        }
        $actual = $node->getAttribute($attr);
        if ($actual !== $value) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                "Failed asserting that attribute '{$attr}' of '{$selector}' equals '{$value}'. Actual: '{$actual}'"
            );
        }
    }

    public static function assertHasClass(string $html, string $selector, string $class): void
    {
        $dom = self::parseHtml($html);
        $xpath = self::selectorToXpath($selector);
        $result = self::xpath($dom)->query($xpath);
        if ($result === false || $result->length === 0) {
            throw new \PHPUnit\Framework\AssertionFailedError("Selector '{$selector}' not found.");
        }
        $node = $result->item(0);
        if (!($node instanceof \DOMElement)) {
            throw new \PHPUnit\Framework\AssertionFailedError("Selector '{$selector}' not found or not an element.");
        }
        $classes = preg_split('/\s+/', $node->getAttribute('class'));
        if (!in_array($class, $classes, true)) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                "Failed asserting that element '{$selector}' has class '{$class}'. Classes: '" . $node->getAttribute('class') . "'"
            );
        }
    }

    public static function assertDataAttributeEquals(string $html, string $selector, string $key, string $value): void
    {
        self::assertAttributeEquals($html, $selector, "data-{$key}", $value);
    }

    public static function assertXPathCount(DOMDocument $dom, string $xpath, int $expected): void
    {
        $result = self::xpath($dom)->query($xpath);
        if ($result === false) {
            throw new \PHPUnit\Framework\AssertionFailedError("Invalid XPath: {$xpath}");
        }
        $actual = $result->length;
        if ($actual !== $expected) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                "Expected XPath count {$expected} for '{$xpath}', got {$actual}"
            );
        }
    }

    public static function assertXPathContains(DOMDocument $dom, string $xpath, string $text): void
    {
        $result = self::xpath($dom)->query($xpath);
        if ($result === false || $result->length === 0) {
            throw new \PHPUnit\Framework\AssertionFailedError("No nodes match XPath: {$xpath}");
        }
        $found = false;
        foreach ($result as $node) {
            if (str_contains($node->textContent ?? '', $text)) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            throw new \PHPUnit\Framework\AssertionFailedError(
                "Failed asserting that XPath '{$xpath}' contains text '{$text}'."
            );
        }
    }

    public static function selectorToXpath(string $selector): string
    {
        $selector = trim($selector);

        if (preg_match('/^#([\w\-]+)$/', $selector, $m)) {
            return "//*[@id='" . htmlspecialchars($m[1], ENT_QUOTES) . "']";
        }

        if (preg_match('/^\.([\w\-]+)$/', $selector, $m)) {
            return "//*[contains(concat(' ', normalize-space(@class), ' '), ' " . htmlspecialchars($m[1], ENT_QUOTES) . " ')]";
        }

        if (preg_match('/^(\w+)?(?:#([\w\-]+))?((?:\.[\w\-]+)*)$/', $selector, $m)) {
            $tag = !empty($m[1]) ? strtolower($m[1]) : '*';
            $idPart = !empty($m[2]) ? "[@id='" . htmlspecialchars($m[2], ENT_QUOTES) . "']" : '';
            $classParts = '';
            if (!empty($m[3])) {
                $classes = array_filter(explode('.', $m[3]));
                foreach ($classes as $cls) {
                    $cls = trim($cls);
                    if ($cls !== '') {
                        $classParts .= "[contains(concat(' ', normalize-space(@class), ' '), ' " . htmlspecialchars($cls, ENT_QUOTES) . " ')]";
                    }
                }
            }
            return "//{$tag}{$idPart}{$classParts}";
        }

        if (str_starts_with($selector, '//') || str_starts_with($selector, '/')) {
            return $selector;
        }

        return "//*[contains(text(), '" . htmlspecialchars($selector, ENT_QUOTES) . "')] or //*[@*='" . htmlspecialchars($selector, ENT_QUOTES) . "']";
    }
}
