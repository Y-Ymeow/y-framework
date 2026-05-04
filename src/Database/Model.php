<?php

declare(strict_types=1);

namespace Framework\Database;

use Framework\Database\Relations\BelongsTo;
use Framework\Database\Relations\HasMany;
use Framework\Database\Relations\HasOne;
use Framework\Database\Relations\BelongsToMany;

abstract class Model implements \ArrayAccess
{
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $guarded = ['*'];
    protected array $hidden = [];
    protected array $casts = [];
    protected array $attributes = [];
    protected array $original = [];
    protected bool $exists = false;
    protected static ?Connection $connection = null;
    protected ?string $connectionName = null;

    private static array $bootedClasses = [];

    private static array $observers = [];
    private static array $globalObservers = [];
    private array $eventListeners = [];

    public static function boot(): void {}

    public static function booted(): void {}

    public static function observe(string|array $classes): void
    {
        foreach ((array) $classes as $class) {
            self::$observers[static::class][] = $class;
        }
    }

    public static function getObservers(): array
    {
        return self::$observers[static::class] ?? [];
    }

    protected static function ensureBooted(): void
    {
        if (isset(self::$bootedClasses[static::class])) {
            return;
        }
        static::boot();
        self::$bootedClasses[static::class] = true;
        static::booted();
    }

    public function __construct(array $attributes = [])
    {
        static::ensureBooted();
        $this->fill($attributes);
    }

    public static function setConnection(Connection $connection): void
    {
        self::$connection = $connection;
    }

    /**
     * 设置模型使用的连接名称
     */
    public function setConnectionName(string $name): void
    {
        $this->connectionName = $name;
    }

    /**
     * 获取模型连接
     * 支持：全局默认连接 → 实例级别连接
     */
    public static function getConnection(): Connection
    {
        if (self::$connection) {
            return self::$connection;
        }

        self::$connection = Connection::get();
        return self::$connection;
    }

    /**
     * 获取当前实例的连接名称（非静态，避免 new static()）
     */
    public function getConnectionName(): ?string
    {
        return $this->connectionName;
    }

    public function getTable(): string
    {
        return $this->table ?? $this->guessTableName();
    }

    private function guessTableName(): string
    {
        $className = (new \ReflectionClass($this))->getShortName();
        $snakeCase = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
        return $snakeCase . 's';
    }

    public function getKeyName(): string
    {
        return $this->primaryKey;
    }

    public function getKey(): mixed
    {
        return $this->attributes[$this->primaryKey] ?? null;
    }

    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }
        return $this;
    }

    protected function isFillable(string $key): bool
    {
        // 如果 guarded 包含 '*'，则默认保护所有字段
        if (in_array('*', $this->guarded, true)) {
            return in_array($key, $this->fillable, true);
        }

        // 检查是否在 guarded 列表中
        if (in_array($key, $this->guarded, true)) {
            return false;
        }

        // 如果 fillable 为空，则允许填充
        if (empty($this->fillable)) {
            return true;
        }

        // 检查是否在 fillable 列表中
        return in_array($key, $this->fillable, true);
    }

    public function setAttribute(string $key, mixed $value): self
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    protected function getAttributesToSave(): array
    {
        $attributes = $this->attributes;

        foreach ($this->casts as $key => $cast) {
            if (isset($attributes[$key])) {
                if (($cast === 'json' || $cast === 'array') && (is_array($attributes[$key]) || is_object($attributes[$key]))) {
                    $attributes[$key] = json_encode($attributes[$key], JSON_UNESCAPED_UNICODE);
                } elseif ($cast === 'datetime' && $attributes[$key] instanceof \DateTimeInterface) {
                    $attributes[$key] = $attributes[$key]->format('Y-m-d H:i:s');
                } elseif ($cast === 'bool' || $cast === 'boolean') {
                    $attributes[$key] = $attributes[$key] ? 1 : 0;
                }
            }
        }

        return $attributes;
    }

    public function getAttribute(string $key): mixed
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->castAttribute($key, $this->attributes[$key]);
        }

        if (method_exists($this, $key)) {
            return $this->$key();
        }

        return null;
    }

    protected function castAttribute(string $key, mixed $value): mixed
    {
        if (!isset($this->casts[$key])) {
            return $value;
        }

        $cast = $this->casts[$key];

        return match ($cast) {
            'int', 'integer' => (int)$value,
            'float', 'double' => (float)$value,
            'string' => (string)$value,
            'bool', 'boolean' => (bool)$value,
            'array' => is_string($value) ? json_decode($value, true) : (array)$value,
            'json' => is_string($value) ? json_decode($value, true) : $value,
            'datetime' => $value instanceof \DateTimeInterface ? $value : new \DateTime($value),
            'timestamp' => is_numeric($value) ? $value : strtotime($value),
            default => $value,
        };
    }

    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }

    public function __set(string $key, mixed $value): void
    {
        $this->setAttribute($key, $value);
    }

    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->$offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->$offset;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->$offset = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->attributes[$offset]);
    }

    public function toArray(): array
    {
        $array = $this->attributes;

        foreach ($this->hidden as $hidden) {
            unset($array[$hidden]);
        }

        foreach ($this->casts as $key => $cast) {
            if (isset($array[$key])) {
                $array[$key] = $this->castAttribute($key, $array[$key]);
            }
        }

        return $array;
    }

    public function toJson(int $options = 0): string
    {
        return json_encode($this->toArray(), $options | JSON_UNESCAPED_UNICODE);
    }

    public static function query(): QueryBuilder
    {
        return (new static())->newQuery();
    }

    public function newQuery(): QueryBuilder
    {
        $query = static::getConnection()->table($this->getTable());

        if (in_array(\Framework\Database\Traits\HasSoftDeletes::class, class_uses(static::class) ?: [], true)) {
            if ($this->softDeleteMode === 'only') {
                $query->whereNotNull('deleted_at');
            } elseif ($this->softDeleteMode !== 'all') {
                $query->whereNull('deleted_at');
            }
        }

        return $query;
    }

    protected string $softDeleteMode = 'exclude';

    public static function all(): array
    {
        return static::query()->get();
    }

    public static function find(mixed $id): ?static
    {
        $instance = new static();
        $result = $instance->newQuery()->find($id, $instance->getKeyName());

        if (!$result) {
            return null;
        }

        return $instance->newFromBuilder($result);
    }

    public static function findOrFail(mixed $id): static
    {
        $model = static::find($id);
        if (!$model) {
            throw new \RuntimeException("Model not found: " . static::class);
        }
        return $model;
    }

    public static function where(string $column, mixed $operator = null, mixed $value = null): QueryBuilder
    {
        return static::query()->where($column, $operator, $value);
    }

    public static function withTrashed(): QueryBuilder
    {
        $instance = new static();
        $instance->softDeleteMode = 'all';
        return $instance->newQuery();
    }

    public static function onlyTrashed(): QueryBuilder
    {
        $instance = new static();
        $instance->softDeleteMode = 'only';
        return $instance->newQuery();
    }

    public static function destroy(mixed $id): int
    {
        $instance = new static();
        return $instance->newQuery()->where($instance->getKeyName(), $id)->delete();
    }

    public static function create(array $attributes): static
    {
        $instance = new static($attributes);
        $instance->save();
        return $instance;
    }

    public function save(): bool
    {
        if ($this->fireModelEvent('saving', true) === false) {
            return false;
        }

        $result = $this->exists ? $this->performUpdate() : $this->performInsert();

        if ($result) {
            $this->fireModelEvent('saved', false);
        }

        return $result;
    }

    protected function fireModelEvent(string $event, bool $halt = false): mixed
    {
        if (!empty($this->eventListeners[$event])) {
            foreach ($this->eventListeners[$event] as $listener) {
                if ($halt) {
                    $result = $listener($this);
                    if ($result === false) {
                        return false;
                    }
                } else {
                    $listener($this);
                }
            }
        }

        $method = $event;
        if (method_exists($this, $method)) {
            $result = $this->$method();
            if ($halt && $result === false) {
                return false;
            }
        }

        foreach (static::getObservers() as $observerClass) {
            if (class_exists($observerClass)) {
                $observer = new $observerClass();
                $methodName = $event;
                if (method_exists($observer, $methodName)) {
                    $result = $observer->$methodName($this);
                    if ($halt && $result === false) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    protected function performInsert(): bool
    {
        if ($this->fireModelEvent('creating', true) === false) {
            return false;
        }

        if ($this->usesTimestamps()) {
            $now = date('Y-m-d H:i:s');
            $this->attributes['created_at'] = $now;
            $this->attributes['updated_at'] = $now;
        }

        $id = $this->newQuery()->insert($this->getAttributesToSave());
        $this->attributes[$this->primaryKey] = $id;
        $this->exists = true;
        $this->original = $this->attributes;

        $this->fireModelEvent('created', false);

        return true;
    }

    protected function performUpdate(): bool
    {
        if ($this->fireModelEvent('updating', true) === false) {
            return false;
        }

        $dirty = $this->getDirty();

        if (empty($dirty)) {
            return true;
        }

        if ($this->usesTimestamps()) {
            $dirty['updated_at'] = date('Y-m-d H:i:s');
        }

        $attributesToSave = $this->getAttributesToSave();
        $dirtyToSave = [];
        foreach ($dirty as $key => $value) {
            $dirtyToSave[$key] = $attributesToSave[$key];
        }

        $this->newQuery()
            ->where($this->primaryKey, $this->getKey())
            ->update($dirtyToSave);

        $this->original = $this->attributes;

        $this->fireModelEvent('updated', false);

        return true;
    }

    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        if ($this->fireModelEvent('deleting', true) === false) {
            return false;
        }

        $this->newQuery()
            ->where($this->primaryKey, $this->getKey())
            ->delete();

        $this->exists = false;

        $this->fireModelEvent('deleted', false);

        return true;
    }

    public function getDirty(): array
    {
        $dirty = [];
        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }
        return $dirty;
    }

    public function isDirty(?string $attribute = null): bool
    {
        $dirty = $this->getDirty();
        if ($attribute === null) {
            return count($dirty) > 0;
        }
        return array_key_exists($attribute, $dirty);
    }

    public function isClean(?string $attribute = null): bool
    {
        return !$this->isDirty($attribute);
    }

    public function wasChanged(?string $attribute = null): bool
    {
        return false;
    }

    public function newFromBuilder(array $attributes): static
    {
        $instance = new static();
        $instance->attributes = $attributes;
        $instance->original = $attributes;
        $instance->exists = true;
        $instance->fireModelEvent('retrieved', false);
        return $instance;
    }

    public function on(string $event, callable $callback): self
    {
        if (!isset($this->eventListeners[$event])) {
            $this->eventListeners[$event] = [];
        }
        $this->eventListeners[$event][] = $callback;
        return $this;
    }

    public function fresh(): ?static
    {
        if (!$this->exists) {
            return null;
        }
        return static::find($this->getKey());
    }

    public function refresh(): static
    {
        if (!$this->exists) {
            return $this;
        }
        $fresh = static::find($this->getKey());
        if ($fresh) {
            $this->attributes = $fresh->attributes;
            $this->original = $fresh->attributes;
        }
        return $this;
    }

    protected function usesTimestamps(): bool
    {
        return true;
    }

    public function hasOne(string $related, string|null $foreignKey = null, string|null $localKey = null): HasOne
    {
        $foreignKey = $foreignKey ?? $this->getForeignKey();
        $localKey = $localKey ?? $this->getKeyName();

        return new HasOne($related, $foreignKey, $localKey, $this);
    }

    public function hasMany(string $related, string|null $foreignKey = null, string|null $localKey = null): HasMany
    {
        $foreignKey = $foreignKey ?? $this->getForeignKey();
        $localKey = $localKey ?? $this->getKeyName();

        return new HasMany($related, $foreignKey, $localKey, $this);
    }

    public function belongsTo(string $related, string|null $foreignKey = null, string|null $ownerKey = null): BelongsTo
    {
        $foreignKey = $foreignKey ?? $this->guessBelongsToForeignKey($related);
        $ownerKey = $ownerKey ?? (new $related())->getKeyName();

        return new BelongsTo($related, $foreignKey, $ownerKey, $this);
    }

    public function belongsToMany(string $related, string|null $table = null, string|null $foreignPivotKey = null, string|null $relatedPivotKey = null): BelongsToMany
    {
        $table = $table ?? $this->joiningTable($related);
        $foreignPivotKey = $foreignPivotKey ?? $this->getForeignKey();
        $relatedPivotKey = $relatedPivotKey ?? (new $related())->getForeignKey();

        return new BelongsToMany($related, $table, $foreignPivotKey, $relatedPivotKey, $this);
    }

    protected function getForeignKey(): string
    {
        $className = (new \ReflectionClass($this))->getShortName();
        $snakeCase = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
        return $snakeCase . '_id';
    }

    protected function guessBelongsToForeignKey(string $related): string
    {
        $className = (new \ReflectionClass($related))->getShortName();
        $snakeCase = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
        return $snakeCase . '_id';
    }

    protected function joiningTable(string $related): string
    {
        $models = [
            $this->getTable(),
            (new $related())->getTable(),
        ];
        sort($models);
        return implode('_', $models);
    }
}
