<?php

declare(strict_types=1);

namespace Framework\Database;

use ArrayAccess;
use Framework\Database\Contracts\ConnectionInterface;
use Framework\Database\Contracts\ModelInterface;
use Framework\Database\Contracts\QueryBuilderInterface;
use Framework\Database\Connection\Manager;
use Framework\Database\Model\ModelNotFoundException;
use Framework\Database\Model\MassAssignmentException;
use Framework\Database\Relations\BelongsTo;
use Framework\Database\Relations\BelongsToMany;
use Framework\Database\Relations\HasMany;
use Framework\Database\Relations\HasOne;
use Framework\Database\Relations\MorphMany;
use Framework\Database\Relations\MorphTo;
use Framework\Database\Scopes\Scope;
use Framework\Support\Collection;

abstract class Model implements ArrayAccess, ModelInterface
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

    protected ?ConnectionInterface $connection = null;

    protected ?string $connectionName = null;

    /** @var array<string, bool> */
    private static array $booted = [];

    /** @var array<class-string, array<string, list<callable>>> */
    private static array $staticEventListeners = [];

    /** @var array<class-string, list<class-string>> */
    private static array $observers = [];

    /** @var array<string, object> */
    private static array $observerInstances = [];

    /** @var array<class-string, array<string, Scope>> */
    private static array $globalScopes = [];

    /** @var array<class-string, callable> */
    private static array $connectionResolver = [];

    /** @var array<class-string, list<string>> */
    private static array $eagerLoad = [];

    private array $eventListeners = [];

    /** @var array<string, mixed> */
    private array $relations = [];

    private string $softDeleteMode = 'exclude';

    public const EVENT_CREATING = 'creating';
    public const EVENT_CREATED = 'created';
    public const EVENT_UPDATING = 'updating';
    public const EVENT_UPDATED = 'updated';
    public const EVENT_SAVING = 'saving';
    public const EVENT_SAVED = 'saved';
    public const EVENT_DELETING = 'deleting';
    public const EVENT_DELETED = 'deleted';
    public const EVENT_RETRIEVED = 'retrieved';

    public static function setConnectionResolver(callable $resolver): void
    {
        self::$connectionResolver[static::class] = $resolver;
    }

    public static function boot(): void {}

    public static function booted(): void {}

    protected static function ensureBooted(): void
    {
        $class = static::class;

        if (isset(self::$booted[$class])) {
            return;
        }

        self::$booted[$class] = true;

        static::boot();

        static::bootTraits();

        static::booted();
    }

    protected static function bootTraits(): void
    {
        $traits = class_uses_recursive(static::class);

        foreach ($traits as $trait) {
            $method = 'boot' . class_basename($trait);
            if (method_exists(static::class, $method)) {
                forward_static_call([static::class, $method]);
            }
        }
    }

    public function __construct(array $attributes = [])
    {
        static::ensureBooted();
        $this->fill($attributes);
    }

    // ---- Global Scopes ----

    public static function addGlobalScope(Scope $scope): void
    {
        $class = static::class;

        if (!isset(self::$globalScopes[$class])) {
            self::$globalScopes[$class] = [];
        }

        $name = $scope::class;
        self::$globalScopes[$class][$name] = $scope;
    }

    public static function removeGlobalScope(string $scopeClass): void
    {
        $class = static::class;

        if (isset(self::$globalScopes[$class][$scopeClass])) {
            unset(self::$globalScopes[$class][$scopeClass]);
        }
    }

    /** @return array<string, Scope> */
    public static function getGlobalScopes(): array
    {
        return self::$globalScopes[static::class] ?? [];
    }

    // ---- Connection ----

    public static function setConnection(ConnectionInterface $connection): void
    {
        self::$connectionResolver[static::class] = fn() => $connection;
    }

    public function setConnectionName(string $name): void
    {
        $this->connectionName = $name;
    }

    public static function resolveConnection(): ConnectionInterface
    {
        $class = static::class;

        if (isset(self::$connectionResolver[$class])) {
            return call_user_func(self::$connectionResolver[$class]);
        }

        if (function_exists('app')) {
            return app(ConnectionInterface::class);
        }

        if (function_exists('db')) {
            return db();
        }

        throw new \RuntimeException('No database connection resolver configured for ' . $class);
    }

    public function getConnection(): ConnectionInterface
    {
        if ($this->connection !== null) {
            return $this->connection;
        }

        if ($this->connectionName !== null) {
            return app(Manager::class)->connection($this->connectionName);
        }

        $this->connection = static::resolveConnection();

        return $this->connection;
    }

    public function getConnectionName(): ?string
    {
        return $this->connectionName;
    }

    // ---- Table / Key ----

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

    public function exists(): bool
    {
        return $this->exists;
    }

    // ---- Fill / Guard ----

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
        if (in_array('*', $this->guarded, true)) {
            return in_array($key, $this->fillable, true);
        }

        if (in_array($key, $this->guarded, true)) {
            return false;
        }

        if (empty($this->fillable)) {
            return true;
        }

        return in_array($key, $this->fillable, true);
    }

    public function setAttribute(string $key, mixed $value): self
    {
        $this->attributes[$key] = $value;

        return $this;
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
            'int', 'integer' => (int) $value,
            'float', 'double' => (float) $value,
            'string' => (string) $value,
            'bool', 'boolean' => (bool) $value,
            'array' => is_string($value) ? json_decode($value, true) : (array) $value,
            'json' => is_string($value) ? json_decode($value, true) : $value,
            'datetime' => $value instanceof \DateTimeInterface ? $value : new \DateTime((string) $value),
            'timestamp' => is_numeric($value) ? $value : strtotime((string) $value),
            default => $value,
        };
    }

    protected function getAttributesToSave(): array
    {
        $attributes = $this->attributes;

        foreach ($this->casts as $key => $cast) {
            if (!isset($attributes[$key])) {
                continue;
            }

            $attributes[$key] = match (true) {
                ($cast === 'json' || $cast === 'array') && (is_array($attributes[$key]) || is_object($attributes[$key]))
                    => json_encode($attributes[$key], JSON_UNESCAPED_UNICODE),
                $cast === 'datetime' && $attributes[$key] instanceof \DateTimeInterface
                    => $attributes[$key]->format('Y-m-d H:i:s'),
                ($cast === 'bool' || $cast === 'boolean')
                    => $attributes[$key] ? 1 : 0,
                default => $attributes[$key],
            };
        }

        return $attributes;
    }

    // ---- Magic accessors ----

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

    // ---- Serialization ----

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

    // ---- Query ----

    public static function query(): QueryBuilderInterface
    {
        return (new static())->newQuery();
    }

    public function newQuery(): QueryBuilderInterface
    {
        $connection = $this->getConnection();
        $query = $connection->table($this->getTable());

        foreach (static::getGlobalScopes() as $scope) {
            $scope->apply($query, $this);
        }

        return $query;
    }

    // ---- CRUD: Read ----

    public static function all(): Collection
    {
        return static::query()->get();
    }

    public static function find(mixed $id): ?static
    {
        $instance = new static();
        $query = $instance->newQuery();
        $row = $query->where($instance->getKeyName(), $id)->first();

        if ($row === null) {
            return null;
        }

        return $instance->newFromBuilder($row);
    }

    public static function findOrFail(mixed $id): static
    {
        $model = static::find($id);

        if ($model === null) {
            throw new ModelNotFoundException(static::class, $id);
        }

        return $model;
    }

    public static function where(string $column, mixed $operator = null, mixed $value = null): QueryBuilderInterface
    {
        return static::query()->where($column, $operator, $value);
    }

    // ---- CRUD: Create ----

    public static function create(array $attributes): static
    {
        $instance = new static($attributes);
        $instance->save();

        return $instance;
    }

    // ---- CRUD: Save ----

    public function save(): bool
    {
        if ($this->fireModelEvent(self::EVENT_SAVING, true) === false) {
            return false;
        }

        $result = $this->exists ? $this->performUpdate() : $this->performInsert();

        if ($result) {
            $this->fireModelEvent(self::EVENT_SAVED, false);
        }

        return $result;
    }

    protected function performInsert(): bool
    {
        if ($this->fireModelEvent(self::EVENT_CREATING, true) === false) {
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

        $this->fireModelEvent(self::EVENT_CREATED, false);

        return true;
    }

    protected function performUpdate(): bool
    {
        if ($this->fireModelEvent(self::EVENT_UPDATING, true) === false) {
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

        $this->fireModelEvent(self::EVENT_UPDATED, false);

        return true;
    }

    // ---- CRUD: Delete ----

    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        if ($this->fireModelEvent(self::EVENT_DELETING, true) === false) {
            return false;
        }

        $this->newQuery()
            ->where($this->primaryKey, $this->getKey())
            ->delete();

        $this->exists = false;

        $this->fireModelEvent(self::EVENT_DELETED, false);

        return true;
    }

    public static function destroy(mixed $id): int
    {
        $instance = new static();

        return $instance->newQuery()
            ->where($instance->getKeyName(), $id)
            ->delete();
    }

    // ---- Dirty tracking ----

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
        if (empty($this->original)) {
            return false;
        }

        $dirty = $this->getDirty();

        if ($attribute === null) {
            return count($dirty) > 0;
        }

        return array_key_exists($attribute, $dirty);
    }

    // ---- Hydration ----

    public function newFromBuilder(Collection|array $attributes): static
    {
        $instance = new static();
        $instance->attributes = $attributes instanceof Collection ? $attributes->toArray() : $attributes;
        $instance->original = $instance->attributes;
        $instance->exists = true;
        $instance->fireModelEvent(self::EVENT_RETRIEVED, false);

        return $instance;
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

        if ($fresh !== null) {
            $this->attributes = $fresh->attributes;
            $this->original = $fresh->attributes;
        }

        return $this;
    }

    // ---- Timestamps ----

    protected function usesTimestamps(): bool
    {
        return true;
    }

    // ---- Events ----

    public static function creating(callable $callback): void
    {
        static::registerStaticEventListener(self::EVENT_CREATING, $callback);
    }

    public static function created(callable $callback): void
    {
        static::registerStaticEventListener(self::EVENT_CREATED, $callback);
    }

    public static function updating(callable $callback): void
    {
        static::registerStaticEventListener(self::EVENT_UPDATING, $callback);
    }

    public static function updated(callable $callback): void
    {
        static::registerStaticEventListener(self::EVENT_UPDATED, $callback);
    }

    public static function saving(callable $callback): void
    {
        static::registerStaticEventListener(self::EVENT_SAVING, $callback);
    }

    public static function saved(callable $callback): void
    {
        static::registerStaticEventListener(self::EVENT_SAVED, $callback);
    }

    public static function deleting(callable $callback): void
    {
        static::registerStaticEventListener(self::EVENT_DELETING, $callback);
    }

    public static function deleted(callable $callback): void
    {
        static::registerStaticEventListener(self::EVENT_DELETED, $callback);
    }

    public static function retrieved(callable $callback): void
    {
        static::registerStaticEventListener(self::EVENT_RETRIEVED, $callback);
    }

    protected static function registerStaticEventListener(string $event, callable $callback): void
    {
        if (!isset(self::$staticEventListeners[static::class])) {
            self::$staticEventListeners[static::class] = [];
        }

        if (!isset(self::$staticEventListeners[static::class][$event])) {
            self::$staticEventListeners[static::class][$event] = [];
        }

        self::$staticEventListeners[static::class][$event][] = $callback;
    }

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

    public function on(string $event, callable $callback): self
    {
        if (!isset($this->eventListeners[$event])) {
            $this->eventListeners[$event] = [];
        }

        $this->eventListeners[$event][] = $callback;

        return $this;
    }

    protected function fireModelEvent(string $event, bool $halt = false): mixed
    {
        $listeners = self::$staticEventListeners[static::class][$event] ?? [];

        foreach ($listeners as $listener) {
            if ($halt) {
                $result = $listener($this);
                if ($result === false) {
                    return false;
                }
            } else {
                $listener($this);
            }
        }

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
        $staticEventMethods = ['creating', 'created', 'updating', 'updated', 'saving', 'saved', 'deleting', 'deleted', 'retrieved'];
        if (method_exists($this, $method) && !in_array($method, $staticEventMethods, true)) {
            $result = $this->$method();
            if ($halt && $result === false) {
                return false;
            }
        }

        foreach (static::getObservers() as $observerClass) {
            if (!class_exists($observerClass)) {
                continue;
            }

            $observerKey = static::class . '@' . $observerClass;
            if (!isset(self::$observerInstances[$observerKey])) {
                self::$observerInstances[$observerKey] = new $observerClass();
            }

            $observer = self::$observerInstances[$observerKey];

            if (method_exists($observer, $event)) {
                $result = $observer->$event($this);
                if ($halt && $result === false) {
                    return false;
                }
            }
        }

        return true;
    }

    // ---- Eager Loading ----

    public static function with(string ...$relations): QueryBuilderInterface
    {
        static::$eagerLoad[static::class] = $relations;
        return static::query();
    }

    public function load(string ...$relations): self
    {
        return $this->loadRelation($relations);
    }

    public function loadMissing(string ...$relations): self
    {
        $toLoad = array_filter($relations, fn($r) => !isset($this->relations[$r]));
        return empty($toLoad) ? $this : $this->loadRelation($toLoad);
    }

    public function loadRelation(array $relations): self
    {
        foreach ($relations as $name => $callback) {
            if (is_numeric($name)) {
                $name = $callback;
                $callback = null;
            }
            $this->loadSingleRelation($name, $callback);
        }
        return $this;
    }

    protected function loadSingleRelation(string $name, ?callable $callback = null): self
    {
        if (!method_exists($this, $name)) {
            return $this;
        }

        $relation = $this->$name();

        if ($callback !== null) {
            $callback($relation);
        }

        $results = $relation->getResults();

        if (is_array($results)) {
            $this->setRelation($name, $results);
        } elseif ($results !== null) {
            $this->setRelation($name, $results);
        }

        return $this;
    }

    public function setRelation(string $name, mixed $value): self
    {
        $this->relations[$name] = $value;
        return $this;
    }

    public function getRelation(string $name): mixed
    {
        return $this->relations[$name] ?? null;
    }

    public function getRelations(): array
    {
        return $this->relations;
    }

    public function relationsToArray(): array
    {
        $result = [];
        foreach ($this->relations as $key => $value) {
            if ($value instanceof Model) {
                $result[$key] = $value->toArray();
            } elseif ($value instanceof Collection) {
                $result[$key] = $value->toArray();
            } elseif (is_array($value)) {
                $result[$key] = array_map(
                    fn($item) => $item instanceof Model ? $item->toArray() : $item,
                    $value
                );
            }
        }
        return $result;
    }

    public static function bootIfNotBooted(): void
    {
        $class = static::class;
        if (!isset(self::$booted[$class])) {
            static::boot();
            self::$booted[$class] = true;
        }
    }

    // ---- Soft Delete helpers ----

    public static function withTrashed(): QueryBuilderInterface
    {
        $instance = new static();
        $instance->softDeleteMode = 'all';

        return $instance->newQuery();
    }

    public static function onlyTrashed(): QueryBuilderInterface
    {
        $instance = new static();
        $instance->softDeleteMode = 'only';

        return $instance->newQuery();
    }

    // ---- Relations ----

    public function hasOne(string $related, ?string $foreignKey = null, ?string $localKey = null): HasOne
    {
        $foreignKey ??= $this->getForeignKey();
        $localKey ??= $this->getKeyName();

        return new HasOne($related, $foreignKey, $localKey, $this);
    }

    public function hasMany(string $related, ?string $foreignKey = null, ?string $localKey = null): HasMany
    {
        $foreignKey ??= $this->getForeignKey();
        $localKey ??= $this->getKeyName();

        return new HasMany($related, $foreignKey, $localKey, $this);
    }

    public function belongsTo(string $related, ?string $foreignKey = null, ?string $ownerKey = null): BelongsTo
    {
        $foreignKey ??= $this->guessBelongsToForeignKey($related);
        $ownerKey ??= (new $related())->getKeyName();

        return new BelongsTo($related, $foreignKey, $ownerKey, $this);
    }

    public function belongsToMany(string $related, ?string $table = null, ?string $foreignPivotKey = null, ?string $relatedPivotKey = null): BelongsToMany
    {
        $table ??= $this->joiningTable($related);
        $foreignPivotKey ??= $this->getForeignKey();
        $relatedPivotKey ??= (new $related())->getForeignKey();

        return new BelongsToMany($related, $table, $foreignPivotKey, $relatedPivotKey, $this);
    }

    public function morphMany(string $related, string $morphType, string $morphId): MorphMany
    {
        return new MorphMany($related, $morphType, $morphId, $this);
    }

    public function morphTo(string $morphType, string $morphId): MorphTo
    {
        return new MorphTo($morphType, $morphId, $this);
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