<?php

declare(strict_types=1);

namespace Framework\Database\Relations;

class BelongsTo extends Relation
{
    protected string $ownerKey;

    public function __construct(string $related, string $foreignKey, string $ownerKey, mixed $parent)
    {
        parent::__construct($related, $foreignKey, $ownerKey, $parent);
        $this->ownerKey = $ownerKey;
    }

    public function getResults(): ?object
    {
        $foreignKeyValue = $this->parent->{$this->foreignKey};
        
        if (!$foreignKeyValue) {
            return null;
        }

        $related = $this->getRelatedInstance();
        return $related::query()
            ->where($this->ownerKey, $foreignKeyValue)
            ->first();
    }
}
