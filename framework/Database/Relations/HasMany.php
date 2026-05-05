<?php

declare(strict_types=1);

namespace Framework\Database\Relations;

class HasMany extends Relation
{
    public function getResults(): array
    {
        $related = $this->getRelatedInstance();
        return $related::query()
            ->where($this->foreignKey, $this->getParentKeyValue())
            ->get();
    }
}
