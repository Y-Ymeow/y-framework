<?php

declare(strict_types=1);

namespace Framework\Database\Relations;

class MorphTo extends Relation
{
    protected string $morphType;
    protected string $morphId;

    public function __construct(string $morphType, string $morphId, mixed $parent)
    {
        $this->morphType = $morphType;
        $this->morphId = $morphId;

        parent::__construct('', $morphId, 'id', $parent);
    }

    public function getResults(): ?object
    {
        $morphClass = $this->parent->{$this->morphType} ?? null;
        $morphId = $this->parent->{$this->morphId} ?? null;

        if (!$morphClass || !$morphId) {
            return null;
        }

        if (!class_exists($morphClass)) {
            return null;
        }

        return $morphClass::find($morphId);
    }
}
