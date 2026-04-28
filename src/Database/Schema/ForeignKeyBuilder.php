<?php

declare(strict_types=1);

namespace Framework\Database\Schema;

class ForeignKeyBuilder
{
    private Blueprint $blueprint;
    private string $column;
    private string $references = 'id';
    private string $on = '';
    private string $onDelete = 'CASCADE';
    private string $onUpdate = 'CASCADE';

    public function __construct(Blueprint $blueprint, string $column)
    {
        $this->blueprint = $blueprint;
        $this->column = $column;
    }

    public function references(string $column): self
    {
        $this->references = $column;
        return $this;
    }

    public function on(string $table): self
    {
        $this->on = $table;
        return $this;
    }

    public function onDelete(string $action): self
    {
        $this->onDelete = $action;
        return $this;
    }

    public function onUpdate(string $action): self
    {
        $this->onUpdate = $action;
        return $this;
    }

    public function cascadeOnDelete(): self
    {
        return $this->onDelete('CASCADE');
    }

    public function restrictOnDelete(): self
    {
        return $this->onDelete('RESTRICT');
    }

    public function nullOnDelete(): self
    {
        return $this->onDelete('SET NULL');
    }

    public function cascadeOnUpdate(): self
    {
        return $this->onUpdate('CASCADE');
    }

    public function restrictOnUpdate(): self
    {
        return $this->onUpdate('RESTRICT');
    }

    public function __destruct()
    {
        $this->blueprint->addForeignKey(
            $this->column,
            $this->references,
            $this->on,
            $this->onDelete,
            $this->onUpdate
        );
    }
}
