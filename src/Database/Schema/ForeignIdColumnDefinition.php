<?php

declare(strict_types=1);

namespace Framework\Database\Schema;

class ForeignIdColumnDefinition
{
    private Blueprint $blueprint;
    private string $column;
    private ?int $foreignKeyIndex = null;

    public function __construct(Blueprint $blueprint, string $column)
    {
        $this->blueprint = $blueprint;
        $this->column = $column;
    }

    public function constrained(?string $table = null, string $references = 'id'): self
    {
        if ($table === null) {
            $table = preg_replace('/_id$/', '', $this->column);
            $table = str_replace('_', '', ucwords($table, '_'));
            $table = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $table)) . 's';
        }

        $this->foreignKeyIndex = $this->blueprint->addForeignKey(
            $this->column,
            $references,
            $table,
            'CASCADE',
            'CASCADE'
        );

        return $this;
    }

    public function cascadeOnUpdate(): self
    {
        if ($this->foreignKeyIndex !== null) {
            $this->blueprint->updateForeignKey($this->foreignKeyIndex, ['on_update' => 'CASCADE']);
        }
        return $this;
    }

    public function cascadeOnDelete(): self
    {
        if ($this->foreignKeyIndex !== null) {
            $this->blueprint->updateForeignKey($this->foreignKeyIndex, ['on_delete' => 'CASCADE']);
        }
        return $this;
    }

    public function restrictOnDelete(): self
    {
        if ($this->foreignKeyIndex !== null) {
            $this->blueprint->updateForeignKey($this->foreignKeyIndex, ['on_delete' => 'RESTRICT']);
        }
        return $this;
    }

    public function nullOnDelete(): self
    {
        if ($this->foreignKeyIndex !== null) {
            $this->blueprint->updateForeignKey($this->foreignKeyIndex, ['on_delete' => 'SET NULL']);
        }
        return $this;
    }

    public function nullable(): self
    {
        $this->blueprint->nullable();
        return $this;
    }

    public function default(mixed $value): self
    {
        $this->blueprint->default($value);
        return $this;
    }

    public function change(): self
    {
        $this->blueprint->change();
        return $this;
    }
}
