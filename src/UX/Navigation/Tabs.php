<?php

declare(strict_types=1);

namespace Framework\UX\Navigation;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class Tabs extends UXComponent
{
    protected array $items = [];
    protected ?string $activeTab = null;
    protected string $variant = 'line';
    protected bool $justified = false;
    protected ?string $liveModel = null;

    public function item(string $label, mixed $content, ?string $id = null, bool $active = false): static
    {
        $id = $id ?? 'tab-' . count($this->items);
        $this->items[] = [
            'id' => $id,
            'label' => $label,
            'content' => $content,
        ];

        if ($active || $this->activeTab === null) {
            $this->activeTab = $id;
        }

        return $this;
    }

    public function activeTab(string $id): static
    {
        $this->activeTab = $id;
        return $this;
    }

    public function variant(string $variant): static
    {
        $this->variant = $variant;
        return $this;
    }

    public function liveModel(string $property): static
    {
        $this->liveModel = $property;
        return $this;
    }

    public function line(): static
    {
        return $this->variant('line');
    }

    public function pills(): static
    {
        return $this->variant('pills');
    }

    public function justified(bool $justified = true): static
    {
        $this->justified = $justified;
        return $this;
    }

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-tabs');
        $el->class("ux-tabs-{$this->variant}");
        if ($this->justified) {
            $el->class('ux-tabs-justified');
        }

        if ($this->liveModel) {
            $el->data('model', $this->liveModel);
        }

        // Tab list
        $listEl = new Element('ul');
        $listEl->class('ux-tabs-nav');
        $listEl->attr('role', 'tablist');

        foreach ($this->items as $item) {
            $isActive = $this->activeTab === $item['id'];

            $liEl = new Element('li');
            $liEl->class('ux-tabs-item');
            if ($isActive) {
                $liEl->class('active');
            }

            $btnEl = new Element('button');
            $btnEl->class('ux-tabs-link');
            $btnEl->attr('type', 'button');
            $btnEl->attr('data-tab-target', '#' . $item['id']);
            $btnEl->attr('role', 'tab');
            $btnEl->attr('aria-selected', $isActive ? 'true' : 'false');

            if ($this->liveModel) {
                $btnEl->data('model-value', $item['id']);
            }

            if ($this->liveAction) {
                $btnEl->liveAction($this->liveAction, $this->liveEvent ?? 'click');
            }

            $btnEl->text($item['label']);
            $liEl->child($btnEl);
            $listEl->child($liEl);
        }

        $el->child($listEl);

        // Content panes
        $contentEl = new Element('div');
        $contentEl->class('ux-tabs-content');

        foreach ($this->items as $item) {
            $isActive = $this->activeTab === $item['id'];

            $paneEl = new Element('div');
            $paneEl->class('ux-tabs-pane');
            if ($isActive) {
                $paneEl->class('active');
                $paneEl->class('show');
            }
            $paneEl->id($item['id']);
            $paneEl->attr('role', 'tabpanel');

            $content = $item['content'];
            if (is_string($content)) {
                $paneEl->html($content);
            } else {
                $paneEl->child($this->resolveChild($content));
            }

            $contentEl->child($paneEl);
        }

        $el->child($contentEl);

        return $el;
    }
}
