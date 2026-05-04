<?php

declare(strict_types=1);

namespace Framework\Database\Traits;

use Framework\Database\Model;

/**
 * HasSoftDeletes 软删除 Trait
 *
 * 为 Model 提供软删除能力，不物理删除记录，而是设置 deleted_at 时间戳。
 *
 * ## 使用方式
 *
 * 1. 在 Model 中 use HasSoftDeletes
 * 2. 数据库需要有 deleted_at 字段（TIMESTAMP，nullable）
 *
 * @example
 * class User extends Model
 * {
 *     use \Framework\Database\Traits\HasSoftDeletes;
 *
 *     protected array $fillable = ['name', 'email'];
 * }
 *
 * // 软删除（设置 deleted_at）
 * $user->delete();
 *
 * // 恢复软删除
 * $user->restore();
 *
 * // 强制物理删除
 * $user->forceDelete();
 *
 * // 查询排除已软删除的记录
 * User::where('name', 'John')->get();  // 自动加 WHERE deleted_at IS NULL
 */
trait HasSoftDeletes
{
    protected string $deletedAtColumn = 'deleted_at';

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
        if ($this->fireModelEvent('deleting', true) === false) {
            return false;
        }

        $time = date('Y-m-d H:i:s');
        $this->{$this->deletedAtColumn} = $time;
        $this->attributes[$this->deletedAtColumn] = $time;

        $this->newQuery()
            ->where($this->primaryKey, $this->getKey())
            ->update([$this->deletedAtColumn => $time]);

        $this->fireModelEvent('deleted', false);

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
