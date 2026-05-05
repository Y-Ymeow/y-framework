<?php

declare(strict_types=1);

namespace Framework\Database\Relations;

abstract class Relation
{
    protected string $related;
    protected mixed $parent;
    protected string $foreignKey;
    protected string $localKey;

    public function __construct(string $related, string $foreignKey, string $localKey, mixed $parent)
    {
        $this->related = $related;
        $this->foreignKey = $foreignKey;
        $this->localKey = $localKey;
        $this->parent = $parent;
    }

    abstract public function getResults(): mixed;

    protected function getRelatedInstance(): mixed
    {
        return new $this->related();
    }

    protected function getParentKeyValue(): mixed
    {
        return $this->parent->{$this->localKey};
    }

    public function getForeignKey(): string
    {
        return $this->foreignKey;
    }

    public function getLocalKey(): string
    {
        return $this->localKey;
    }

    public function getRelated(): string
    {
        return $this->related;
    }

    public function getParent(): mixed
    {
        return $this->parent;
    }
}