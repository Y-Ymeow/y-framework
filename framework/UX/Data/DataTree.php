<?php

declare(strict_types=1);

namespace Framework\UX\Data;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

/**
 * 树形数据
 *
 * 用于展示树形结构数据，支持节点展开/折叠、单选、多选、图标、线条、自定义节点渲染。
 *
 * @ux-category Data
 * @ux-since 1.0.0
 * @ux-example DataTree::make()->treeData($tree)->showIcon()->showLine()
 * @ux-example DataTree::make()->treeData($deptTree)->selectable()->selectAction('selectDept')
 * @ux-example DataTree::make()->treeData($files)->checkable()->checkAction('checkFiles')->defaultExpandAll()
 * @ux-js-component data-tree.js
 * @ux-css data-tree.css
 */
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

    /**
     * 设置树形数据
     * @param array $data 树形数据数组（每个节点需有 key/id, title/label/name）
     * @return static
     * @ux-example DataTree::make()->treeData($treeData)
     */
    public function treeData(array $data): static
    {
        $this->treeData = $data;
        return $this;
    }

    /**
     * 自定义节点标题渲染
     * @param \Closure $callback 回调函数，接收 ($node)
     * @return static
     */
    public function renderTitle(\Closure $callback): static
    {
        $this->renderTitle = $callback;
        return $this;
    }

    /**
     * 设置树变体
     * @param string $variant 变体：default
     * @return static
     * @ux-default 'default'
     */
    public function variant(string $variant): static
    {
        $this->variant = $variant;
        return $this;
    }

    /**
     * 设置是否显示连接线
     * @param bool $showLine 是否显示
     * @return static
     * @ux-default false
     */
    public function showLine(bool $showLine = true): static
    {
        $this->showLine = $showLine;
        return $this;
    }

    /**
     * 设置是否显示图标
     * @param bool $showIcon 是否显示
     * @return static
     * @ux-default false
     */
    public function showIcon(bool $showIcon = true): static
    {
        $this->showIcon = $showIcon;
        return $this;
    }

    /**
     * 设置是否可选（单选）
     * @param bool $selectable 是否可选
     * @return static
     * @ux-default false
     */
    public function selectable(bool $selectable = true): static
    {
        $this->selectable = $selectable;
        return $this;
    }

    /**
     * 设置是否可勾选（多选）
     * @param bool $checkable 是否可勾选
     * @return static
     * @ux-default false
     */
    public function checkable(bool $checkable = true): static
    {
        $this->checkable = $checkable;
        return $this;
    }

    /**
     * 设置是否默认展开所有节点
     * @param bool $expand 是否展开
     * @return static
     * @ux-default false
     */
    public function defaultExpandAll(bool $expand = true): static
    {
        $this->defaultExpandAll = $expand;
        return $this;
    }

    /**
     * 设置默认展开的节点 key 列表
     * @param array $keys 节点 key 数组
     * @return static
     */
    public function defaultExpandedKeys(array $keys): static
    {
        $this->defaultExpandedKeys = $keys;
        return $this;
    }

    /**
     * 设置空数据提示文字
     * @param string $text 提示文字
     * @return static
     */
    public function emptyText(string $text): static
    {
        $this->emptyText = $text;
        return $this;
    }

    /**
     * 设置树标题
     * @param string $title 标题
     * @return static
     */
    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    /**
     * 设置自定义头部内容
     * @param mixed $header 自定义内容（Element 或组件）
     * @return static
     */
    public function header(mixed $header): static
    {
        $this->header = $header;
        return $this;
    }

    /**
     * 设置分片名称（用于 Live 局部更新）
     * @param string $name 分片名
     * @return static
     */
    public function fragment(string $name): static
    {
        $this->fragmentName = $name;
        return $this;
    }

    /**
     * 设置节点展开/折叠动作
     * @param string $action LiveAction 名称
     * @return static
     */
    public function toggleAction(string $action): static
    {
        $this->toggleAction = $action;
        return $this;
    }

    /**
     * 设置节点选择动作
     * @param string $action LiveAction 名称
     * @return static
     */
    public function selectAction(string $action): static
    {
        $this->selectAction = $action;
        return $this;
    }

    /**
     * 设置节点勾选动作
     * @param string $action LiveAction 名称
     * @return static
     */
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
            $emptyText = $this->emptyText ?? t('ux:data-tree.empty_data');
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
                    $toggleEl->liveAction($toggleAction, 'click', [
                        'key' => (string)$key,
                        'expanded' => !$isExpanded,
                    ]);
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
                    $checkboxEl->liveAction($checkAction, 'change', [
                        'key' => (string)$key,
                    ]);
                }

                $contentEl->child($checkboxEl);
            }

            $titleEl = Element::make('span')->class('ux-data-tree-node-title');

            if ($this->selectable) {
                $selectAction = $this->selectAction ?? $this->liveAction;
                if ($selectAction) {
                    $titleEl->liveAction($selectAction, 'click', [
                        'key' => (string)$key,
                    ]);
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
