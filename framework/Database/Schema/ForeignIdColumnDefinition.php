<?php

declare(strict_types=1);

namespace Framework\Database\Schema;

class ForeignIdColumnDefinition
{
    private Blueprint $blueprint;
    private string $column;

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

        $this->blueprint->addForeignKey(
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
        return $this;
    }

    public function cascadeOnDelete(): self
    {
        return $this;
    }

    public function restrictOnDelete(): self
    {
        return $this;
    }

    public function nullOnDelete(): self
    {
        return $this;
    }
}
