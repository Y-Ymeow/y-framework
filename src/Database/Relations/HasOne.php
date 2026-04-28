<?php

declare(strict_types=1);

namespace Framework\Database\Relations;

class HasOne extends Relation
{
    public function getResults(): ?object
    {
        $related = $this->getRelatedInstance();
        return $related::query()
            ->where($this->foreignKey, $this->getParentKeyValue())
            ->first();
    }
}
