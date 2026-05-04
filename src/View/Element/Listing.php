<?php

declare(strict_types=1);

namespace Framework\View\Element;

use Framework\View\Base\Element;

class Listing extends Element
{
    private array $items = [];

    public function __construct(?string $tag = 'ul')
    {
        if ($tag === null) $tag = 'ul';
        parent::__construct($tag);
    }

    public static function ul(): static { return new static('ul'); }
    public static function ol(): static { return new static('ol'); }
    public static function dl(): static { return new static('dl'); }

    public function items(array $items): static
    {
        $this->items = $items;
        return $this;
    }

    public function pairs(array $pairs): static
    {
        foreach ($pairs as $term => $description) {
            $this->child((new Element('dt'))->text((string)$term));
            $dd = new Element('dd');
            if ($description instanceof Element) {
                $dd->child($description);
            } else {
                $dd->text((string)$description);
            }
            $this->child($dd);
        }
        return $this;
    }

    public function render(): string
    {
        foreach ($this->items as $item) {
            if ($item instanceof Element) {
                $this->child($item);
            } else {
                $this->child((new Element('li'))->text((string)$item));
            }
        }
        $this->items = [];

        return parent::render();
    }

    public function listDisc(): static { return $this->class('list-disc'); }
    public function listDecimal(): static { return $this->class('list-decimal'); }
    public function listNone(): static { return $this->class('list-none'); }
}
