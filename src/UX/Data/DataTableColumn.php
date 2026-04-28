<?php

declare(strict_types=1);

namespace Framework\UX\Data;

class DataTableColumn
{
    public string $dataKey;
    public string $title;
    public ?\Closure $render = null;
    public ?string $width = null;
    public ?string $align = null;
    public bool $sortable = false;
    public ?string $fixed = null;
    public bool $visible = true;
    public ?string $tooltip = null;

    public bool $searchable = false;
    public string $searchType = 'like';
    public ?array $searchOptions = null;

    public function __construct(string $dataKey, string $title)
    {
        $this->dataKey = $dataKey;
        $this->title = $title;
    }

    public static function make(string $dataKey, string $title): static
    {
        return new static($dataKey, $title);
    }

    public function width(?string $width): static
    {
        $this->width = $width;
        return $this;
    }

    public function align(?string $align): static
    {
        $this->align = $align;
        return $this;
    }

    public function alignCenter(): static
    {
        return $this->align('center');
    }

    public function alignRight(): static
    {
        return $this->align('right');
    }

    public function alignLeft(): static
    {
        return $this->align('left');
    }

    public function sortable(bool $sortable = true): static
    {
        $this->sortable = $sortable;
        return $this;
    }

    public function fixed(?string $position): static
    {
        $this->fixed = $position;
        return $this;
    }

    public function fixedLeft(): static
    {
        return $this->fixed('left');
    }

    public function fixedRight(): static
    {
        return $this->fixed('right');
    }

    public function visible(bool $visible = true): static
    {
        $this->visible = $visible;
        return $this;
    }

    public function hidden(): static
    {
        return $this->visible(false);
    }

    public function tooltip(?string $tooltip): static
    {
        $this->tooltip = $tooltip;
        return $this;
    }

    public function render(\Closure $callback): static
    {
        $this->render = $callback;
        return $this;
    }

    public function searchable(bool $searchable = true, string $type = 'like', ?array $options = null): static
    {
        $this->searchable = $searchable;
        $this->searchType = $type;
        $this->searchOptions = $options;
        return $this;
    }

    public function searchEqual(): static
    {
        return $this->searchable(true, '=');
    }

    public function searchLike(): static
    {
        return $this->searchable(true, 'like');
    }

    public function searchIn(?array $options = null): static
    {
        return $this->searchable(true, 'in', $options);
    }

    public function toArray(): array
    {
        return [
            'dataKey' => $this->dataKey,
            'title' => $this->title,
            'render' => $this->render,
            'width' => $this->width,
            'align' => $this->align,
            'sortable' => $this->sortable,
            'fixed' => $this->fixed,
            'visible' => $this->visible,
            'tooltip' => $this->tooltip,
            'searchable' => $this->searchable,
            'searchType' => $this->searchType,
            'searchOptions' => $this->searchOptions,
        ];
    }
}
