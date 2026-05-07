<?php

declare(strict_types=1);

namespace Framework\UX\Form\Layout;

use Framework\UX\Form\Contracts\FormLayout;
use Framework\UX\Form\Concerns\HasComponents;
use Framework\View\Base\Element;

class Section implements FormLayout
{
    use HasComponents;

    protected string|array|null $title = null;
    protected ?string $description = null;
    protected bool $collapsible = false;
    protected bool $collapsed = false;

    public static function make(string|array|null $title = null): static
    {
        $instance = new static();
        $instance->title = $title;
        return $instance;
    }

    public function title(string|array $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function description(string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function collapsible(bool $collapsible = true): static
    {
        $this->collapsible = $collapsible;
        return $this;
    }

    public function collapsed(bool $collapsed = true): static
    {
        $this->collapsed = $collapsed;
        return $this;
    }

    public function getName(): string
    {
        return '';
    }

    public function setName(string $name): static
    {
        return $this;
    }

    public function getLabel(): string|array|null
    {
        return $this->title;
    }

    public function setLabel(string|array $label): static
    {
        $this->title = $label;
        return $this;
    }

    public function isRequired(): bool
    {
        return false;
    }

    public function required(bool $required = true): static
    {
        return $this;
    }

    public function isDisabled(): bool
    {
        return false;
    }

    public function disabled(bool $disabled = true): static
    {
        return $this;
    }

    public function getValue(): mixed
    {
        return null;
    }

    public function setValue(mixed $value): static
    {
        return $this;
    }

    public function getDefault(): mixed
    {
        return null;
    }

    public function default(mixed $default): static
    {
        return $this;
    }

    public function render(): Element
    {
        $section = Element::make('div')->class('ux-form-section');

        if ($this->title !== null) {
            $header = Element::make('div')->class('ux-form-section-header');

            $titleEl = Element::make('h3')->class('ux-form-section-title');
            if (is_array($this->title)) {
                $key = $this->title[0] ?? '';
                $params = $this->title[1] ?? [];
                $default = $this->title[2] ?? '';
                $titleEl->child(Element::make('span')->intl($key, $params, $default));
            } else {
                $titleEl->text($this->title);
            }

            $header->child($titleEl);

            if ($this->description) {
                $header->child(
                    Element::make('p')->class('ux-form-section-description')->text($this->description)
                );
            }

            $section->child($header);
        }

        $content = Element::make('div')->class('ux-form-section-content');
        foreach ($this->renderComponents() as $element) {
            $content->child($element);
        }
        $section->child($content);

        return $section;
    }
}
