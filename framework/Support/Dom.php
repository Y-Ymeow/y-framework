<?php

declare(strict_types=1);

namespace Framework\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

class Dom
{
    private DOMDocument $dom;
    private DOMXPath $xpath;

    public function __construct(string $html)
    {
        libxml_use_internal_errors(true);
        $this->dom = new DOMDocument();
        
        // 确保正确处理 UTF-8 编码，并移除不必要的 DTD 声明
        $html = '<?xml encoding="UTF-8">' . $html;
        $this->dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        
        libxml_clear_errors();
        $this->xpath = new DOMXPath($this->dom);
    }

    /**
     * 静态加载 HTML
     */
    public static function load(string $html): self
    {
        return new self($html);
    }

    /**
     * 执行 XPath 查询
     */
    public function query(string $expression, ?DOMNode $contextNode = null)
    {
        return $this->xpath->query($expression, $contextNode);
    }

    /**
     * 获取第一个匹配的元素
     */
    public function find(string $expression, ?DOMNode $contextNode = null): ?DOMElement
    {
        $nodes = $this->query($expression, $contextNode);
        return ($nodes && $nodes->length > 0) ? $nodes->item(0) : null;
    }

    /**
     * 获取节点的 Inner HTML (不包含节点自身标签)
     */
    public function getInnerHtml(DOMNode $node): string
    {
        $innerHtml = '';
        foreach ($node->childNodes as $child) {
            $innerHtml .= $this->dom->saveHTML($child);
        }
        return $innerHtml;
    }

    /**
     * 获取节点的 Outer HTML (包含节点自身标签)
     */
    public function getOuterHtml(DOMNode $node): string
    {
        return $this->dom->saveHTML($node);
    }

    /**
     * 获取标题 (便捷方法)
     */
    public function getTitle(): string
    {
        $title = $this->find('//title');
        return $title ? trim($title->textContent) : '';
    }

    /**
     * 获取 Body 内容 (便捷方法)
     */
    public function getBodyContent(): string
    {
        $body = $this->find('//body');
        return $body ? $this->getInnerHtml($body) : '';
    }

    /**
     * 安全地获取元素属性
     */
    public function attr(DOMNode $node, string $name, string $default = ''): string
    {
        if ($node instanceof DOMElement) {
            return $node->getAttribute($name);
        }
        return $default;
    }

    public function getDocument(): DOMDocument
    {
        return $this->dom;
    }
}
