# 数据库模块 — API 参考

> 由 DocGen 自动生成于 2026-05-03 15:49:15

## 目录

**其他**
- [`BelongsTo`](#framework-database-relations-belongsto)
- [`BelongsToMany`](#framework-database-relations-belongstomany)
- [`Blueprint`](#framework-database-schema-blueprint)
- [`Connection`](#framework-database-connection)
- [`DatabaseServiceProvider`](#framework-database-databaseserviceprovider)
- [`ForeignKeyBuilder`](#framework-database-schema-foreignkeybuilder)
- [`HasMany`](#framework-database-relations-hasmany)
- [`HasOne`](#framework-database-relations-hasone)
- [`Migration`](#framework-database-migration-migration)
- [`Model`](#framework-database-model)
- [`QueryBuilder`](#framework-database-querybuilder)
- [`Relation`](#framework-database-relations-relation)
- [`Schema`](#framework-database-schema-schema)
- [`SqlValidator`](#framework-database-sqlvalidator)

---

### 其他

<a name="framework-database-relations-belongsto"></a>
#### `Framework\Database\Relations\BelongsTo`

**继承:** `Framework\Database\Relations\Relation`  | **文件:** `php/src/Database/Relations/BelongsTo.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `getResults` |  | — |


<a name="framework-database-relations-belongstomany"></a>
#### `Framework\Database\Relations\BelongsToMany`

**继承:** `Framework\Database\Relations\Relation`  | **文件:** `php/src/Database/Relations/BelongsToMany.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `getResults` |  | — |
| `attach` |  | `mixed $id`, `array $attributes` = [] |
| `detach` |  | `mixed $id` |
| `sync` |  | `array $ids` |


<a name="framework-database-schema-blueprint"></a>
#### `Framework\Database\Schema\Blueprint`

**文件:** `php/src/Database/Schema/Blueprint.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `id` |  | `string $column` = 'id' |
| `bigIncrements` |  | `string $column` = 'id' |
| `uuid` |  | `string $column` |
| `string` |  | `string $column`, `int $length` = 255 |
| `text` |  | `string $column` |
| `longText` |  | `string $column` |
| `integer` |  | `string $column`, `bool $unsigned` = false |
| `bigInteger` |  | `string $column`, `bool $unsigned` = false |
| `tinyInteger` |  | `string $column`, `bool $unsigned` = false |
| `boolean` |  | `string $column` |
| `decimal` |  | `string $column`, `int $precision` = 10, `int $scale` = 2 |
| `float` |  | `string $column`, `int $precision` = 10, `int $scale` = 2 |
| `double` |  | `string $column`, `int $precision` = 15, `int $scale` = 8 |
| `date` |  | `string $column` |
| `datetime` |  | `string $column` |
| `timestamp` |  | `string $column` |
| `timestamps` |  | — |
| `softDeletes` |  | `string $column` = 'deleted_at' |
| `json` |  | `string $column` |
| `enum` |  | `string $column`, `array $values` |
| `set` |  | `string $column`, `array $values` |
| `nullable` |  | — |
| `default` |  | `mixed $value` |
| `unsigned` |  | — |
| `unique` |  | `string $column` = '' |
| `index` |  | `string $column` = '' |
| `foreign` |  | `string $column` |
| `addForeignKey` |  | `string $column`, `string $references`, `string $on`, `string $onDelete` = 'CASCADE', `string $onUpdate` = 'CASCADE' |
| `rememberToken` |  | — |
| `toSql` |  | — |
| `toDropSql` |  | — |
| `getTable` |  | — |


<a name="framework-database-connection"></a>
#### `Framework\Database\Connection`

**文件:** `php/src/Database/Connection.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `setLogger` |  | `Psr\Log\LoggerInterface $logger` |
| `make` |  | `array $config` |
| `getPdo` |  | — |
| `getDriverName` |  | — |
| `query` |  | `string $sql`, `array $bindings` = [] |
| `queryOne` |  | `string $sql`, `array $bindings` = [] |
| `execute` |  | `string $sql`, `array $bindings` = [] |
| `getQueries` |  | — |
| `getTotalQueryTime` |  | — |
| `insert` |  | `string $table`, `array $data` |
| `update` |  | `string $table`, `array $data`, `string $where`, `array $whereBindings` = [] |
| `delete` |  | `string $table`, `string $where`, `array $bindings` = [] |
| `table` |  | `string $table` |
| `transaction` |  | `callable $callback` |
| `getQueryCount` |  | — |
| `getPrefix` |  | — |


<a name="framework-database-databaseserviceprovider"></a>
#### `Framework\Database\DatabaseServiceProvider`

**继承:** `Framework\Foundation\ServiceProvider`  | **文件:** `php/src/Database/DatabaseServiceProvider.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `register` |  | — |


<a name="framework-database-schema-foreignkeybuilder"></a>
#### `Framework\Database\Schema\ForeignKeyBuilder`

**文件:** `php/src/Database/Schema/ForeignKeyBuilder.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `references` |  | `string $column` |
| `on` |  | `string $table` |
| `onDelete` |  | `string $action` |
| `onUpdate` |  | `string $action` |
| `cascadeOnDelete` |  | — |
| `restrictOnDelete` |  | — |
| `nullOnDelete` |  | — |
| `cascadeOnUpdate` |  | — |
| `restrictOnUpdate` |  | — |


<a name="framework-database-relations-hasmany"></a>
#### `Framework\Database\Relations\HasMany`

**继承:** `Framework\Database\Relations\Relation`  | **文件:** `php/src/Database/Relations/HasMany.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `getResults` |  | — |


<a name="framework-database-relations-hasone"></a>
#### `Framework\Database\Relations\HasOne`

**继承:** `Framework\Database\Relations\Relation`  | **文件:** `php/src/Database/Relations/HasOne.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `getResults` |  | — |


<a name="framework-database-migration-migration"></a>
#### `Framework\Database\Migration\Migration`

**abstract**  | **文件:** `php/src/Database/Migration/Migration.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `up` |  | — |
| `down` |  | — |


<a name="framework-database-model"></a>
#### `Framework\Database\Model`

**实现:** `ArrayAccess`  | **abstract**  | **文件:** `php/src/Database/Model.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `setConnection` |  | `Framework\Database\Connection $connection` |
| `getConnection` |  | — |
| `getTable` |  | — |
| `getKeyName` |  | — |
| `getKey` |  | — |
| `fill` |  | `array $attributes` |
| `setAttribute` |  | `string $key`, `mixed $value` |
| `getAttribute` |  | `string $key` |
| `offsetExists` |  | `mixed $offset` |
| `offsetGet` |  | `mixed $offset` |
| `offsetSet` |  | `mixed $offset`, `mixed $value` |
| `offsetUnset` |  | `mixed $offset` |
| `toArray` |  | — |
| `toJson` |  | `int $options` = 0 |
| `query` |  | — |
| `newQuery` |  | — |
| `all` |  | — |
| `find` |  | `mixed $id` |
| `findOrFail` |  | `mixed $id` |
| `where` |  | `string $column`, `mixed $operator` = null, `mixed $value` = null |
| `destroy` |  | `mixed $id` |
| `create` |  | `array $attributes` |
| `save` |  | — |
| `delete` |  | — |
| `getDirty` |  | — |
| `newFromBuilder` |  | `array $attributes` |
| `hasOne` |  | `string $related`, `?string $foreignKey` = null, `?string $localKey` = null |
| `hasMany` |  | `string $related`, `?string $foreignKey` = null, `?string $localKey` = null |
| `belongsTo` |  | `string $related`, `?string $foreignKey` = null, `?string $ownerKey` = null |
| `belongsToMany` |  | `string $related`, `?string $table` = null, `?string $foreignPivotKey` = null, `?string $relatedPivotKey` = null |


<a name="framework-database-querybuilder"></a>
#### `Framework\Database\QueryBuilder`

**文件:** `php/src/Database/QueryBuilder.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `select` |  | `string $columns` |
| `where` |  | `string $column`, `mixed $operator` = null, `mixed $value` = null, `string $boolean` = 'AND' |
| `orWhere` |  | `string $column`, `mixed $operator` = null, `mixed $value` = null |
| `whereIn` |  | `string $column`, `array $values` |
| `whereNull` |  | `string $column` |
| `whereNotNull` |  | `string $column` |
| `whereLike` |  | `string $column`, `string $pattern` |
| `whereBetween` |  | `string $column`, `mixed $min`, `mixed $max` |
| `join` |  | `string $table`, `string $first`, `string $operator`, `string $second`, `string $type` = 'INNER' |
| `leftJoin` |  | `string $table`, `string $first`, `string $operator`, `string $second` |
| `rightJoin` |  | `string $table`, `string $first`, `string $operator`, `string $second` |
| `orderBy` |  | `string $column`, `string $direction` = 'ASC' |
| `latest` |  | `string $column` = 'created_at' |
| `oldest` |  | `string $column` = 'created_at' |
| `limit` |  | `int $limit` |
| `offset` |  | `int $offset` |
| `groupBy` |  | `string $columns` |
| `having` |  | `string $column`, `string $operator`, `mixed $value` |
| `havingRaw` |  | `string $sql`, `array $bindings` = [] |
| `whereNotBetween` |  | `string $column`, `mixed $min`, `mixed $max` |
| `whereDate` |  | `string $column`, `string $operator`, `mixed $value` |
| `whereDay` |  | `string $column`, `string $operator`, `mixed $value` |
| `whereMonth` |  | `string $column`, `string $operator`, `mixed $value` |
| `whereYear` |  | `string $column`, `string $operator`, `mixed $value` |
| `whereColumn` |  | `string $column1`, `string $operator`, `string $column2` |
| `whereExists` |  | `Framework\Database\QueryBuilder $query` |
| `whereNotExists` |  | `Framework\Database\QueryBuilder $query` |
| `distinct` |  | `bool $distinct` = true |
| `groupByRaw` |  | `string $sql`, `array $bindings` = [] |
| `orderByRaw` |  | `string $sql`, `array $bindings` = [] |
| `orderByDesc` |  | `string $column` |
| `lock` |  | `?string $lock` = 'FOR UPDATE' |
| `sharedLock` |  | — |
| `lockForUpdate` |  | — |
| `increment` |  | `string $column`, `int\|float $amount` = 1 |
| `decrement` |  | `string $column`, `int\|float $amount` = 1 |
| `get` |  | — |
| `first` |  | — |
| `find` |  | `mixed $id`, `string $column` = 'id' |
| `count` |  | `string $column` = '*' |
| `sum` |  | `string $column` |
| `avg` |  | `string $column` |
| `max` |  | `string $column` |
| `min` |  | `string $column` |
| `exists` |  | — |
| `doesntExist` |  | — |
| `paginate` |  | `int $perPage` = 15, `int $page` = 1 |
| `insert` |  | `array $data` |
| `update` |  | `array $data` |
| `delete` |  | — |
| `toSql` |  | — |
| `getBindings` |  | — |


<a name="framework-database-relations-relation"></a>
#### `Framework\Database\Relations\Relation`

**abstract**  | **文件:** `php/src/Database/Relations/Relation.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `getResults` |  | — |


<a name="framework-database-schema-schema"></a>
#### `Framework\Database\Schema\Schema`

**文件:** `php/src/Database/Schema/Schema.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `create` |  | `string $table`, `callable $callback` |
| `drop` |  | `string $table` |
| `dropIfExists` |  | `string $table` |
| `table` |  | `string $table`, `callable $callback` |
| `hasTable` |  | `string $table` |
| `hasColumn` |  | `string $table`, `string $column` |
| `rename` |  | `string $from`, `string $to` |
| `addColumn` |  | `string $table`, `string $column`, `string $type`, `array $options` = [] |
| `dropColumn` |  | `string $table`, `string $column` |


<a name="framework-database-sqlvalidator"></a>
#### `Framework\Database\SqlValidator`

**文件:** `php/src/Database/SqlValidator.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `validateColumn` |  | `string $column` |
| `validateTable` |  | `string $table` |
| `validateOperator` |  | `string $operator` |
| `validateDirection` |  | `string $direction` |
| `validateAlias` |  | `string $alias` |
| `validateColumns` |  | `array $columns` |
| `escapeIdentifier` |  | `string $identifier` |


