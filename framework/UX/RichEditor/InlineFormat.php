<?php

declare(strict_types=1);

namespace Framework\UX\RichEditor;

use Framework\View\Base\Element;

class InlineFormat
{
    public string $name = '';
    public string $title = '';
    public string $icon = '';
    public string $tag = '';
    public array $attributes = [];
    public bool $isVoid = false;

    private ?\Closure $renderElementCallback = null;

    public function __construct(string $name = '')
    {
        if ($name !== '') {
            $this->name = $name;
        }
    }

    public static function make(string $name): static
    {
        return new static($name);
    }

    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function tag(string $tag): static
    {
        $this->tag = $tag;
        return $this;
    }

    public function attribute(string $name, array $schema): static
    {
        $this->attributes[$name] = array_merge([
            'type' => 'string',
            'default' => null,
        ], $schema);
        return $this;
    }

    public function void(bool $isVoid = true): static
    {
        $this->isVoid = $isVoid;
        return $this;
    }

    public function withRenderElement(\Closure $callback): static
    {
        $this->renderElementCallback = $callback;
        return $this;
    }

    public function renderElement(array $formatAttrs, Element|string $inner): Element
    {
        if ($this->renderElementCallback) {
            return ($this->renderElementCallback)($formatAttrs, $inner);
        }

        return $this->defaultRenderElement($formatAttrs, $inner);
    }

    protected function defaultRenderElement(array $formatAttrs, Element|string $inner): Element
    {
        $tag = $this->tag ?: 'span';
        $el = Element::make($tag);

        foreach ($formatAttrs as $key => $value) {
            if ($value === true) {
                continue;
            }
            if (is_string($value) || is_numeric($value)) {
                $el->attr($key, (string)$value);
            }
        }

        if ($this->name !== $tag) {
            $el->data('format', $this->name);
        }

        $el->child($inner);

        return $el;
    }

    public function getDefaultAttributes(): array
    {
        $defaults = [];
        foreach ($this->attributes as $name => $schema) {
            if (isset($schema['default'])) {
                $defaults[$name] = $schema['default'];
            }
        }
        return $defaults;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'title' => $this->title,
            'icon' => $this->icon,
            'tag' => $this->tag,
            'attributes' => $this->attributes,
            'isVoid' => $this->isVoid,
            'defaultAttributes' => $this->getDefaultAttributes(),
        ];
    }
}
