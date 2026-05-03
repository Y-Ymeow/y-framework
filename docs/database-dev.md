# 数据库模块 — 开发文档

> 由 DocGen 自动生成于 2026-05-03 15:49:15

## 组件清单

| 名称 | 命名空间 | 文件 | 类型 |
|---|---|---|---|
| `BelongsTo` | `Framework\Database\Relations` | `php/src/Database/Relations/BelongsTo.php` | extends Framework\Database\Relations\Relation |
| `BelongsToMany` | `Framework\Database\Relations` | `php/src/Database/Relations/BelongsToMany.php` | extends Framework\Database\Relations\Relation |
| `Blueprint` | `Framework\Database\Schema` | `php/src/Database/Schema/Blueprint.php` | class |
| `Connection` | `Framework\Database` | `php/src/Database/Connection.php` | class |
| `DatabaseServiceProvider` | `Framework\Database` | `php/src/Database/DatabaseServiceProvider.php` | extends Framework\Foundation\ServiceProvider |
| `ForeignKeyBuilder` | `Framework\Database\Schema` | `php/src/Database/Schema/ForeignKeyBuilder.php` | class |
| `HasMany` | `Framework\Database\Relations` | `php/src/Database/Relations/HasMany.php` | extends Framework\Database\Relations\Relation |
| `HasOne` | `Framework\Database\Relations` | `php/src/Database/Relations/HasOne.php` | extends Framework\Database\Relations\Relation |
| `Migration` | `Framework\Database\Migration` | `php/src/Database/Migration/Migration.php` | abstract |
| `Model` | `Framework\Database` | `php/src/Database/Model.php` | abstract |
| `QueryBuilder` | `Framework\Database` | `php/src/Database/QueryBuilder.php` | class |
| `Relation` | `Framework\Database\Relations` | `php/src/Database/Relations/Relation.php` | abstract |
| `Schema` | `Framework\Database\Schema` | `php/src/Database/Schema/Schema.php` | class |
| `SqlValidator` | `Framework\Database` | `php/src/Database/SqlValidator.php` | class |

---

## 详细实现

### `Framework\Database\Relations\BelongsTo`

- **文件:** `php/src/Database/Relations/BelongsTo.php`
- **继承:** `Framework\Database\Relations\Relation`

**公开方法 (1)：**

- `getResults(): ?object`

### `Framework\Database\Relations\BelongsToMany`

- **文件:** `php/src/Database/Relations/BelongsToMany.php`
- **继承:** `Framework\Database\Relations\Relation`

**公开方法 (4)：**

- `getResults(): array`
- `attach(mixed $id, array $attributes = []): void`
- `detach(mixed $id): void`
- `sync(array $ids): void`

### `Framework\Database\Schema\Blueprint`

- **文件:** `php/src/Database/Schema/Blueprint.php`

**公开方法 (32)：**

- `id(string $column = 'id'): Framework\Database\Schema\Blueprint`
- `bigIncrements(string $column = 'id'): Framework\Database\Schema\Blueprint`
- `uuid(string $column): Framework\Database\Schema\Blueprint`
- `string(string $column, int $length = 255): Framework\Database\Schema\Blueprint`
- `text(string $column): Framework\Database\Schema\Blueprint`
- `longText(string $column): Framework\Database\Schema\Blueprint`
- `integer(string $column, bool $unsigned = false): Framework\Database\Schema\Blueprint`
- `bigInteger(string $column, bool $unsigned = false): Framework\Database\Schema\Blueprint`
- `tinyInteger(string $column, bool $unsigned = false): Framework\Database\Schema\Blueprint`
- `boolean(string $column): Framework\Database\Schema\Blueprint`
- `decimal(string $column, int $precision = 10, int $scale = 2): Framework\Database\Schema\Blueprint`
- `float(string $column, int $precision = 10, int $scale = 2): Framework\Database\Schema\Blueprint`
- `double(string $column, int $precision = 15, int $scale = 8): Framework\Database\Schema\Blueprint`
- `date(string $column): Framework\Database\Schema\Blueprint`
- `datetime(string $column): Framework\Database\Schema\Blueprint`
- `timestamp(string $column): Framework\Database\Schema\Blueprint`
- `timestamps(): Framework\Database\Schema\Blueprint`
- `softDeletes(string $column = 'deleted_at'): Framework\Database\Schema\Blueprint`
- `json(string $column): Framework\Database\Schema\Blueprint`
- `enum(string $column, array $values): Framework\Database\Schema\Blueprint`
- `set(string $column, array $values): Framework\Database\Schema\Blueprint`
- `nullable(): Framework\Database\Schema\Blueprint`
- `default(mixed $value): Framework\Database\Schema\Blueprint`
- `unsigned(): Framework\Database\Schema\Blueprint`
- `unique(string $column = ''): Framework\Database\Schema\Blueprint`
- `index(string $column = ''): Framework\Database\Schema\Blueprint`
- `foreign(string $column): Framework\Database\Schema\ForeignKeyBuilder`
- `addForeignKey(string $column, string $references, string $on, string $onDelete = 'CASCADE', string $onUpdate = 'CASCADE'): Framework\Database\Schema\Blueprint`
- `rememberToken(): Framework\Database\Schema\Blueprint`
- `toSql(): string`
- `toDropSql(): string`
- `getTable(): string`

### `Framework\Database\Connection`

- **文件:** `php/src/Database/Connection.php`

**公开方法 (16)：**

- `setLogger(Psr\Log\LoggerInterface $logger): void`
- `make(array $config): Framework\Database\Connection`
- `getPdo(): PDO`
- `getDriverName(): string`
- `query(string $sql, array $bindings = []): array`
- `queryOne(string $sql, array $bindings = []): ?array`
- `execute(string $sql, array $bindings = []): PDOStatement`
- `getQueries(): array`
- `getTotalQueryTime(): string`
- `insert(string $table, array $data): int`
- `update(string $table, array $data, string $where, array $whereBindings = []): int`
- `delete(string $table, string $where, array $bindings = []): int`
- `table(string $table): Framework\Database\QueryBuilder`
- `transaction(callable $callback): mixed`
- `getQueryCount(): int`
- `getPrefix(): string`

### `Framework\Database\DatabaseServiceProvider`

- **文件:** `php/src/Database/DatabaseServiceProvider.php`
- **继承:** `Framework\Foundation\ServiceProvider`

**公开方法 (1)：**

- `register(): void`

### `Framework\Database\Schema\ForeignKeyBuilder`

- **文件:** `php/src/Database/Schema/ForeignKeyBuilder.php`

**公开方法 (9)：**

- `references(string $column): Framework\Database\Schema\ForeignKeyBuilder`
- `on(string $table): Framework\Database\Schema\ForeignKeyBuilder`
- `onDelete(string $action): Framework\Database\Schema\ForeignKeyBuilder`
- `onUpdate(string $action): Framework\Database\Schema\ForeignKeyBuilder`
- `cascadeOnDelete(): Framework\Database\Schema\ForeignKeyBuilder`
- `restrictOnDelete(): Framework\Database\Schema\ForeignKeyBuilder`
- `nullOnDelete(): Framework\Database\Schema\ForeignKeyBuilder`
- `cascadeOnUpdate(): Framework\Database\Schema\ForeignKeyBuilder`
- `restrictOnUpdate(): Framework\Database\Schema\ForeignKeyBuilder`

### `Framework\Database\Relations\HasMany`

- **文件:** `php/src/Database/Relations/HasMany.php`
- **继承:** `Framework\Database\Relations\Relation`

**公开方法 (1)：**

- `getResults(): array`

### `Framework\Database\Relations\HasOne`

- **文件:** `php/src/Database/Relations/HasOne.php`
- **继承:** `Framework\Database\Relations\Relation`

**公开方法 (1)：**

- `getResults(): ?object`

### `Framework\Database\Migration\Migration`

- **文件:** `php/src/Database/Migration/Migration.php`

**公开方法 (2)：**

- `up(): void`
- `down(): void`

### `Framework\Database\Model`

- **文件:** `php/src/Database/Model.php`

**公开方法 (30)：**

- `setConnection(Framework\Database\Connection $connection): void`
- `getConnection(): Framework\Database\Connection`
- `getTable(): string`
- `getKeyName(): string`
- `getKey(): mixed`
- `fill(array $attributes): Framework\Database\Model`
- `setAttribute(string $key, mixed $value): Framework\Database\Model`
- `getAttribute(string $key): mixed`
- `offsetExists(mixed $offset): bool`
- `offsetGet(mixed $offset): mixed`
- `offsetSet(mixed $offset, mixed $value): void`
- `offsetUnset(mixed $offset): void`
- `toArray(): array`
- `toJson(int $options = 0): string`
- `query(): Framework\Database\QueryBuilder`
- `newQuery(): Framework\Database\QueryBuilder`
- `all(): array`
- `find(mixed $id): ?static`
- `findOrFail(mixed $id): static`
- `where(string $column, mixed $operator = null, mixed $value = null): Framework\Database\QueryBuilder`
- `destroy(mixed $id): int`
- `create(array $attributes): static`
- `save(): bool`
- `delete(): bool`
- `getDirty(): array`
- `newFromBuilder(array $attributes): static`
- `hasOne(string $related, ?string $foreignKey = null, ?string $localKey = null): Framework\Database\Relations\HasOne`
- `hasMany(string $related, ?string $foreignKey = null, ?string $localKey = null): Framework\Database\Relations\HasMany`
- `belongsTo(string $related, ?string $foreignKey = null, ?string $ownerKey = null): Framework\Database\Relations\BelongsTo`
- `belongsToMany(string $related, ?string $table = null, ?string $foreignPivotKey = null, ?string $relatedPivotKey = null): Framework\Database\Relations\BelongsToMany`

### `Framework\Database\QueryBuilder`

- **文件:** `php/src/Database/QueryBuilder.php`

**公开方法 (52)：**

- `select(string $columns): Framework\Database\QueryBuilder`
- `where(string $column, mixed $operator = null, mixed $value = null, string $boolean = 'AND'): Framework\Database\QueryBuilder`
- `orWhere(string $column, mixed $operator = null, mixed $value = null): Framework\Database\QueryBuilder`
- `whereIn(string $column, array $values): Framework\Database\QueryBuilder`
- `whereNull(string $column): Framework\Database\QueryBuilder`
- `whereNotNull(string $column): Framework\Database\QueryBuilder`
- `whereLike(string $column, string $pattern): Framework\Database\QueryBuilder`
- `whereBetween(string $column, mixed $min, mixed $max): Framework\Database\QueryBuilder`
- `join(string $table, string $first, string $operator, string $second, string $type = 'INNER'): Framework\Database\QueryBuilder`
- `leftJoin(string $table, string $first, string $operator, string $second): Framework\Database\QueryBuilder`
- `rightJoin(string $table, string $first, string $operator, string $second): Framework\Database\QueryBuilder`
- `orderBy(string $column, string $direction = 'ASC'): Framework\Database\QueryBuilder`
- `latest(string $column = 'created_at'): Framework\Database\QueryBuilder`
- `oldest(string $column = 'created_at'): Framework\Database\QueryBuilder`
- `limit(int $limit): Framework\Database\QueryBuilder`
- `offset(int $offset): Framework\Database\QueryBuilder`
- `groupBy(string $columns): Framework\Database\QueryBuilder`
- `having(string $column, string $operator, mixed $value): Framework\Database\QueryBuilder`
- `havingRaw(string $sql, array $bindings = []): Framework\Database\QueryBuilder`
- `whereNotBetween(string $column, mixed $min, mixed $max): Framework\Database\QueryBuilder`
- `whereDate(string $column, string $operator, mixed $value): Framework\Database\QueryBuilder`
- `whereDay(string $column, string $operator, mixed $value): Framework\Database\QueryBuilder`
- `whereMonth(string $column, string $operator, mixed $value): Framework\Database\QueryBuilder`
- `whereYear(string $column, string $operator, mixed $value): Framework\Database\QueryBuilder`
- `whereColumn(string $column1, string $operator, string $column2): Framework\Database\QueryBuilder`
- `whereExists(Framework\Database\QueryBuilder $query): Framework\Database\QueryBuilder`
- `whereNotExists(Framework\Database\QueryBuilder $query): Framework\Database\QueryBuilder`
- `distinct(bool $distinct = true): Framework\Database\QueryBuilder`
- `groupByRaw(string $sql, array $bindings = []): Framework\Database\QueryBuilder`
- `orderByRaw(string $sql, array $bindings = []): Framework\Database\QueryBuilder`
- `orderByDesc(string $column): Framework\Database\QueryBuilder`
- `lock(?string $lock = 'FOR UPDATE'): Framework\Database\QueryBuilder`
- `sharedLock(): Framework\Database\QueryBuilder`
- `lockForUpdate(): Framework\Database\QueryBuilder`
- `increment(string $column, int|float $amount = 1): int`
- `decrement(string $column, int|float $amount = 1): int`
- `get(): array`
- `first(): ?array`
- `find(mixed $id, string $column = 'id'): ?array`
- `count(string $column = '*'): int`
- `sum(string $column): mixed`
- `avg(string $column): mixed`
- `max(string $column): mixed`
- `min(string $column): mixed`
- `exists(): bool`
- `doesntExist(): bool`
- `paginate(int $perPage = 15, int $page = 1): array`
- `insert(array $data): int`
- `update(array $data): int`
- `delete(): int`
- `toSql(): string`
- `getBindings(): array`

### `Framework\Database\Relations\Relation`

- **文件:** `php/src/Database/Relations/Relation.php`

**公开方法 (1)：**

- `getResults(): mixed`

### `Framework\Database\Schema\Schema`

- **文件:** `php/src/Database/Schema/Schema.php`

**公开方法 (9)：**

- `create(string $table, callable $callback): void`
- `drop(string $table): void`
- `dropIfExists(string $table): void`
- `table(string $table, callable $callback): void`
- `hasTable(string $table): bool`
- `hasColumn(string $table, string $column): bool`
- `rename(string $from, string $to): void`
- `addColumn(string $table, string $column, string $type, array $options = []): void`
- `dropColumn(string $table, string $column): void`

### `Framework\Database\SqlValidator`

- **文件:** `php/src/Database/SqlValidator.php`

**公开方法 (7)：**

- `validateColumn(string $column): string`
- `validateTable(string $table): string`
- `validateOperator(string $operator): string`
- `validateDirection(string $direction): string`
- `validateAlias(string $alias): string`
- `validateColumns(array $columns): array`
- `escapeIdentifier(string $identifier): string`

