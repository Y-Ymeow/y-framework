<?php

declare(strict_types=1);

namespace Framework\UX\Data;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class DataTree extends UXComponent
{
    protected array $treeData = [];
    protected ?\Closure $renderTitle = null;
    protected string $variant = 'default';
    protected bool $showLine = false;
    protected bool $showIcon = false;
    protected bool $selectable = false;
    protected bool $checkable = false;
    protected bool $defaultExpandAll = false;
    protected array $defaultExpandedKeys = [];
    protected ?string $emptyText = null;
    protected ?string $title = null;
    protected mixed $header = null;

    protected ?string $fragmentName = null;
    protected ?string $toggleAction = null;
    protected ?string $selectAction = null;
    protected ?string $checkAction = null;

    public function treeData(array $data): static
    {
        $this->treeData = $data;
        return $this;
    }

    public function renderTitle(\Closure $callback): static
    {
        $this->renderTitle = $callback;
        return $this;
    }

    public function variant(string $variant): static
    {
        $this->variant = $variant;
        return $this;
    }

    public function showLine(bool $showLine = true): static
    {
        $this->showLine = $showLine;
        return $this;
    }

    public function showIcon(bool $showIcon = true): static
    {
        $this->showIcon = $showIcon;
        return $this;
    }

    public function selectable(bool $selectable = true): static
    {
        $this->selectable = $selectable;
        return $this;
    }

    public function checkable(bool $checkable = true): static
    {
        $this->checkable = $checkable;
        return $this;
    }

    public function defaultExpandAll(bool $expand = true): static
    {
        $this->defaultExpandAll = $expand;
        return $this;
    }

    public function defaultExpandedKeys(array $keys): static
    {
        $this->defaultExpandedKeys = $keys;
        return $this;
    }

    public function emptyText(string $text): static
    {
        $this->emptyText = $text;
        return $this;
    }

    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function header(mixed $header): static
    {
        $this->header = $header;
        return $this;
    }

    public function fragment(string $name): static
    {
        $this->fragmentName = $name;
        return $this;
    }

    public function toggleAction(string $action): static
    {
        $this->toggleAction = $action;
        return $this;
    }

    public function selectAction(string $action): static
    {
        $this->selectAction = $action;
        return $this;
    }

    public function checkAction(string $action): static
    {
        $this->checkAction = $action;
        return $this;
    }

    protected function toElement(): Element
    {
        $wrapper = new Element('div');
        $this->buildElement($wrapper);
        $wrapper->class('ux-data-tree-wrapper');

        if ($this->title || $this->header) {
            $headerEl = Element::make('div')->class('ux-data-tree-header');
            if ($this->title) {
                $headerEl->child(Element::make('div')->class('ux-data-tree-title')->text($this->title));
            }
            if ($this->header) {
                $headerEl->child(Element::make('div')->class('ux-data-tree-header-extra')->child($this->resolveChild($this->header)));
            }
            $wrapper->child($headerEl);
        }

        $treeEl = Element::make('div')->class('ux-data-tree');
        $treeEl->class("ux-data-tree-{$this->variant}");

        if ($this->showLine) {
            $treeEl->class('ux-data-tree-show-line');
        }
        if ($this->showIcon) {
            $treeEl->class('ux-data-tree-show-icon');
        }
        if ($this->selectable) {
            $treeEl->class('ux-data-tree-selectable');
        }
        if ($this->checkable) {
            $treeEl->class('ux-data-tree-checkable');
        }

        if ($this->fragmentName) {
            $treeEl->liveFragment($this->fragmentName);
        }

        if (empty($this->treeData)) {
            $emptyText = $this->emptyText ?? '暂无数据';
            $treeEl->child(
                Element::make('div')->class('ux-data-tree-empty')->text($emptyText)
            );
        } else {
            $treeEl->child($this->buildTreeNodes($this->treeData, 0));
        }

        $wrapper->child($treeEl);

        return $wrapper;
    }

    protected function buildTreeNodes(array $nodes, int $level): Element
    {
        $listEl = Element::make('ul')->class('ux-data-tree-list');
        if ($level > 0) {
            $listEl->class('ux-data-tree-list-nested');
        }

        foreach ($nodes as $node) {
            $key = $node['key'] ?? $node['id'] ?? '';
            $title = $node['title'] ?? $node['label'] ?? $node['name'] ?? '';
            $children = $node['children'] ?? [];
            $hasChildren = !empty($children);
            $isExpanded = $this->defaultExpandAll || in_array($key, $this->defaultExpandedKeys);

            $liEl = Element::make('li')->class('ux-data-tree-node');
            $liEl->data('key', (string)$key);
            $liEl->data('level', (string)$level);

            if ($hasChildren) {
                $liEl->class('ux-data-tree-node-parent');
            }
            if ($isExpanded && $hasChildren) {
                $liEl->class('ux-data-tree-node-expanded');
            }

            $contentEl = Element::make('div')->class('ux-data-tree-node-content');

            if ($hasChildren) {
                $toggleEl = Element::make('span')
                    ->class('ux-data-tree-toggle')
                    ->text($isExpanded ? '▼' : '▶');

                $toggleAction = $this->toggleAction ?? $this->liveAction;
                if ($toggleAction) {
                    $toggleEl->liveAction($toggleAction, 'click');
                    $toggleEl->data('action-params', json_encode([
                        'key' => (string)$key,
                        'expanded' => !$isExpanded,
                    ], JSON_UNESCAPED_UNICODE));
                } else {
                    $toggleEl->data('action', 'toggle');
                }

                $contentEl->child($toggleEl);
            } else {
                $contentEl->child(
                    Element::make('span')->class('ux-data-tree-toggle ux-data-tree-toggle-leaf')->text(' ')
                );
            }

            if ($this->showIcon) {
                $icon = $node['icon'] ?? ($hasChildren ? '📁' : '📄');
                $contentEl->child(
                    Element::make('span')->class('ux-data-tree-icon')->text($icon)
                );
            }

            if ($this->checkable) {
                $checkboxEl = Element::make('input')
                    ->attr('type', 'checkbox')
                    ->class('ux-data-tree-checkbox')
                    ->data('key', (string)$key);

                $checkAction = $this->checkAction ?? $this->liveAction;
                if ($checkAction) {
                    $checkboxEl->liveAction($checkAction, 'change');
                    $checkboxEl->data('action-params', json_encode([
                        'key' => (string)$key,
                    ], JSON_UNESCAPED_UNICODE));
                }

                $contentEl->child($checkboxEl);
            }

            $titleEl = Element::make('span')->class('ux-data-tree-node-title');

            if ($this->selectable) {
                $selectAction = $this->selectAction ?? $this->liveAction;
                if ($selectAction) {
                    $titleEl->liveAction($selectAction, 'click');
                    $titleEl->data('action-params', json_encode([
                        'key' => (string)$key,
                    ], JSON_UNESCAPED_UNICODE));
                } else {
                    $titleEl->data('action', 'select');
                    $titleEl->data('key', (string)$key);
                }
            }

            if ($this->renderTitle) {
                $rendered = ($this->renderTitle)($node);
                if ($rendered instanceof Element || $rendered instanceof UXComponent) {
                    $titleEl->child($this->resolveChild($rendered));
                } elseif (is_string($rendered)) {
                    $titleEl->html($rendered);
                } else {
                    $titleEl->text($title);
                }
            } else {
                $titleEl->text($title);
            }

            $contentEl->child($titleEl);
            $liEl->child($contentEl);

            if ($hasChildren) {
                $childList = $this->buildTreeNodes($children, $level + 1);
                if (!$isExpanded) {
                    $childList->attr('style', 'display:none');
                }
                $liEl->child($childList);
            }

            $listEl->child($liEl);
        }

        return $listEl;
    }
}
