<?php

declare(strict_types=1);

namespace Framework\UX\Data;

use Framework\UX\UXComponent;
use Framework\View\Base\Element;

class DataCard extends UXComponent
{
    protected array $fields = [];
    protected array $dataSource = [];
    protected string $variant = 'default';
    protected ?string $title = null;
    protected ?string $subtitle = null;
    protected mixed $avatar = null;
    protected mixed $actions = null;
    protected mixed $cover = null;
    protected bool $bordered = false;
    protected ?string $fragmentName = null;

    public function field(string $label, string $dataKey, ?\Closure $render = null, array $options = []): static
    {
        $this->fields[] = array_merge([
            'label' => $label,
            'dataKey' => $dataKey,
            'render' => $render,
            'span' => null,
        ], $options);
        return $this;
    }

    public function fields(array $fields): static
    {
        foreach ($fields as $field) {
            $this->field(
                $field['label'] ?? '',
                $field['dataKey'] ?? $field['key'] ?? '',
                $field['render'] ?? null,
                $field
            );
        }
        return $this;
    }

    public function dataSource(array $data): static
    {
        $this->dataSource = $data;
        return $this;
    }

    public function item(array $data): static
    {
        return $this->dataSource($data);
    }

    public function variant(string $variant): static
    {
        $this->variant = $variant;
        return $this;
    }

    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function subtitle(string $subtitle): static
    {
        $this->subtitle = $subtitle;
        return $this;
    }

    public function avatar(mixed $avatar): static
    {
        $this->avatar = $avatar;
        return $this;
    }

    public function actions(mixed $actions): static
    {
        $this->actions = $actions;
        return $this;
    }

    public function cover(mixed $cover): static
    {
        $this->cover = $cover;
        return $this;
    }

    public function bordered(bool $bordered = true): static
    {
        $this->bordered = $bordered;
        return $this;
    }

    public function fragment(string $name): static
    {
        $this->fragmentName = $name;
        return $this;
    }

    protected function toElement(): Element
    {
        $el = new Element('div');
        $this->buildElement($el);
        $el->class('ux-data-card');
        $el->class("ux-data-card-{$this->variant}");

        if ($this->bordered) {
            $el->class('ux-data-card-bordered');
        }

        if ($this->fragmentName) {
            $el->liveFragment($this->fragmentName);
        }

        if ($this->cover) {
            $coverEl = Element::make('div')->class('ux-data-card-cover');
            $coverEl->child($this->resolveChild($this->cover));
            $el->child($coverEl);
        }

        $headerEl = Element::make('div')->class('ux-data-card-header');
        $hasHeader = false;

        if ($this->avatar) {
            $avatarEl = Element::make('div')->class('ux-data-card-avatar');
            $avatarEl->child($this->resolveChild($this->avatar));
            $headerEl->child($avatarEl);
            $hasHeader = true;
        }

        if ($this->title || $this->subtitle) {
            $titleWrapper = Element::make('div')->class('ux-data-card-title-wrapper');
            if ($this->title) {
                $titleText = is_string($this->title) && isset($this->dataSource[$this->title])
                    ? (string)$this->dataSource[$this->title]
                    : $this->title;
                $titleWrapper->child(Element::make('div')->class('ux-data-card-title')->text($titleText));
            }
            if ($this->subtitle) {
                $subtitleText = is_string($this->subtitle) && isset($this->dataSource[$this->subtitle])
                    ? (string)$this->dataSource[$this->subtitle]
                    : $this->subtitle;
                $titleWrapper->child(Element::make('div')->class('ux-data-card-subtitle')->text($subtitleText));
            }
            $headerEl->child($titleWrapper);
            $hasHeader = true;
        }

        if ($this->actions) {
            $actionsEl = Element::make('div')->class('ux-data-card-actions');
            $actionsEl->child($this->resolveChild($this->actions));
            $headerEl->child($actionsEl);
            $hasHeader = true;
        }

        if ($hasHeader) {
            $el->child($headerEl);
        }

        if (!empty($this->fields)) {
            $bodyEl = Element::make('div')->class('ux-data-card-body');

            foreach ($this->fields as $field) {
                $fieldEl = Element::make('div')->class('ux-data-card-field');

                $labelEl = Element::make('div')
                    ->class('ux-data-card-field-label')
                    ->text($field['label']);
                $fieldEl->child($labelEl);

                $value = $this->dataSource[$field['dataKey']] ?? null;
                $valueEl = Element::make('div')->class('ux-data-card-field-value');

                if (isset($field['render']) && $field['render'] instanceof \Closure) {
                    $rendered = ($field['render'])($value, $this->dataSource);
                    if ($rendered instanceof Element || $rendered instanceof UXComponent) {
                        $valueEl->child($this->resolveChild($rendered));
                    } elseif (is_string($rendered)) {
                        $valueEl->html($rendered);
                    } else {
                        $valueEl->text((string)($value ?? '-'));
                    }
                } else {
                    $valueEl->text((string)($value ?? '-'));
                }

                $fieldEl->child($valueEl);
                $bodyEl->child($fieldEl);
            }

            $el->child($bodyEl);
        }

        $this->appendChildren($el);

        return $el;
    }
}
