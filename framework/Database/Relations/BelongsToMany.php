<?php

declare(strict_types=1);

namespace Framework\Database\Relations;

class BelongsToMany extends Relation
{
    protected string $table;
    protected string $relatedPivotKey;

    public function __construct(string $related, string $table, string $foreignPivotKey, string $relatedPivotKey, mixed $parent)
    {
        parent::__construct($related, $foreignPivotKey, $relatedPivotKey, $parent);
        $this->table = $table;
        $this->relatedPivotKey = $relatedPivotKey;
    }

    public function getResults(): array
    {
        $related = $this->getRelatedInstance();
        $relatedTable = $related->getTable();
        $relatedKey = $related->getKeyName();

        $sql = "SELECT {$relatedTable}.* FROM {$relatedTable}
                INNER JOIN {$this->table} ON {$relatedTable}.{$relatedKey} = {$this->table}.{$this->relatedPivotKey}
                WHERE {$this->table}.{$this->foreignKey} = ?";

        $connection = $related::getConnection();
        $results = $connection->query($sql, [$this->getParentKeyValue()]);

        return array_map(fn($row) => $related->newFromBuilder($row), $results);
    }

    public function attach(mixed $id, array $attributes = []): void
    {
        $related = $this->getRelatedInstance();
        $connection = $related::getConnection();

        $data = array_merge([
            $this->foreignKey => $this->getParentKeyValue(),
            $this->relatedPivotKey => $id,
        ], $attributes);

        $connection->insert($this->table, $data);
    }

    public function detach(mixed $id): void
    {
        $related = $this->getRelatedInstance();
        $connection = $related::getConnection();

        $connection->delete(
            $this->table,
            "{$this->foreignKey} = ? AND {$this->relatedPivotKey} = ?",
            [$this->getParentKeyValue(), $id]
        );
    }

    public function sync(array $ids): void
    {
        $related = $this->getRelatedInstance();
        $connection = $related::getConnection();

        $connection->delete($this->table, "{$this->foreignKey} = ?", [$this->getParentKeyValue()]);

        foreach ($ids as $id) {
            $this->attach($id);
        }
    }
}
