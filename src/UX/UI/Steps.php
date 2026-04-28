<?php

declare(strict_types=1);

namespace Framework\UX\UI;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class Steps extends UXComponent
{
    protected array $items = [];
    protected int $current = 0;
    protected string $direction = 'horizontal';

    public function item(string $title, ?string $description = null): static
    {
        $this->items[] = [
            'title' => $title,
            'description' => $description,
        ];
        return $this;
    }

    public function current(int $current): static
    {
        $this->current = $current;
        return $this;
    }

    public function vertical(): static
    {
        $this->direction = 'vertical';
        return $this;
    }

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);

        $el->class('ux-steps');
        $el->class("ux-steps-{$this->direction}");

        foreach ($this->items as $index => $item) {
            $status = $index < $this->current ? 'finish' : ($index === $this->current ? 'process' : 'wait');

            $itemEl = new Element('div');
            $itemEl->class('ux-steps-item');
            $itemEl->class("ux-steps-item-{$status}");

            // Icon
            $iconEl = new Element('div');
            $iconEl->class('ux-steps-item-icon');
            if ($status === 'finish') {
                $iconEl->html('✓');
            } else {
                $iconEl->text((string)($index + 1));
            }

            // Content
            $contentEl = new Element('div');
            $contentEl->class('ux-steps-item-content');

            $titleEl = new Element('div');
            $titleEl->class('ux-steps-item-title');
            $titleEl->text($item['title']);
            $contentEl->child($titleEl);

            if ($item['description']) {
                $descEl = new Element('div');
                $descEl->class('ux-steps-item-description');
                $descEl->text($item['description']);
                $contentEl->child($descEl);
            }

            // Container
            $containerEl = new Element('div');
            $containerEl->class('ux-steps-item-container');
            $containerEl->child($iconEl);
            $containerEl->child($contentEl);

            // Tail (except last item)
            if ($index < count($this->items) - 1) {
                $tailEl = new Element('div');
                $tailEl->class('ux-steps-item-tail');
                $containerEl->child($tailEl);
            }

            $itemEl->child($containerEl);
            $el->child($itemEl);
        }

        return $el;
    }
}
