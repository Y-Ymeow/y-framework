<?php

declare(strict_types=1);

namespace Framework\Database\Traits;

use Framework\Database\Model;
use Framework\Database\Scopes\SoftDeletingScope;

trait HasSoftDeletes
{
    protected string $deletedAtColumn = 'deleted_at';

    public static function bootHasSoftDeletes(): void
    {
        static::addGlobalScope(new SoftDeletingScope());
    }

    public function getDeletedAtColumn(): string
    {
        return $this->deletedAtColumn;
    }

    public function initializeSoftDeletes(): void
    {
        if (!in_array($this->deletedAtColumn, $this->casts)) {
            $this->casts[$this->deletedAtColumn] = 'datetime';
        }
    }

    public function trashed(): bool
    {
        return !is_null($this->{$this->deletedAtColumn});
    }

    public function delete(): bool
    {
        if ($this->fireModelEvent(Model::EVENT_DELETING, true) === false) {
            return false;
        }

        $time = date('Y-m-d H:i:s');
        $this->{$this->deletedAtColumn} = $time;
        $this->attributes[$this->deletedAtColumn] = $time;

        $this->newQuery()
            ->where($this->primaryKey, $this->getKey())
            ->update([$this->deletedAtColumn => $time]);

        $this->fireModelEvent(Model::EVENT_DELETED, false);

        return true;
    }

    public function forceDelete(): bool
    {
        if ($this->fireModelEvent('forceDeleting', true) === false) {
            return false;
        }

        $this->newQuery()
            ->where($this->primaryKey, $this->getKey())
            ->delete();

        $this->exists = false;

        return true;
    }

    public function restore(): bool
    {
        if (!$this->trashed()) {
            return true;
        }

        if ($this->fireModelEvent('restoring', true) === false) {
            return false;
        }

        $this->newQuery()
            ->where($this->primaryKey, $this->getKey())
            ->update([$this->deletedAtColumn => null]);

        unset($this->attributes[$this->deletedAtColumn]);
        unset($this->original[$this->deletedAtColumn]);

        $this->fireModelEvent('restored', false);

        return true;
    }
}