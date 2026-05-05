<?php

declare(strict_types=1);

namespace Framework\Database\Relations;

class MorphMany extends Relation
{
    protected string $morphType;
    protected string $morphId;

    public function __construct(string $related, string $morphType, string $morphId, mixed $parent)
    {
        $this->morphType = $morphType;
        $this->morphId = $morphId;

        parent::__construct($related, $morphId, 'id', $parent);
    }

    public function getResults(): array
    {
        $related = $this->getRelatedInstance();
        $morphClass = get_class($this->parent);

        return $related::query()
            ->where($this->morphType, $morphClass)
            ->where($this->morphId, $this->getParentKeyValue())
            ->get();
    }

    public function create(array $attributes = []): mixed
    {
        $related = $this->getRelatedInstance();
        $attributes[$this->morphType] = get_class($this->parent);
        $attributes[$this->morphId] = $this->getParentKeyValue();

        return $related::create($attributes);
    }
}
