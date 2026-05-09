# 数据库与 Model 文档

> 框架的数据库层：ORM、Query Builder、Schema、Migration、关系、Trait 全景。

---

## 目录

1. [架构概览](#1-架构概览)
2. [连接管理](#2-连接管理)
3. [Model — ORM 核心](#3-model--orm-核心)
4. [Query Builder](#4-query-builder)
5. [关系系统](#5-关系系统)
6. [Schema 与 Migration](#6-schema-与-migration)
7. [Global Scopes](#7-global-scopes)
8. [Trait 扩展](#8-trait-扩展)
9. [事件与 Observer](#9-事件与-observer)
10. [完整 API 速查](#10-完整-api-速查)

---

## 1. 架构概览

```
DatabaseServiceProvider
  ├── Connection\Manager         连接管理器（多连接支持）
  │   └── Connection             单个数据库连接（PDO 封装）
  │       └── Query\Builder      查询构建器
  │           └── Query\Grammar  SQL 语法编译器（MySQL/SQLite/Postgres）
  │
  ├── Model                      ORM 基类（Active Record 模式）
  │   ├── Relations\*            关系系统（HasMany/BelongsTo/BelongsToMany/MorphMany/MorphTo）
  │   ├── Scopes\*               全局作用域（SoftDeletingScope）
  │   └── Traits\*               扩展 Trait（HasSoftDeletes/HasTranslations/HasAuth）
  │
  ├── Schema\Schema              Schema 构建器
  │   └── Schema\Blueprint       表结构定义
  │       └── Schema\Grammar     DDL 语法编译器
  │
  └── Migration\Migration        迁移基类
```

### 核心设计原则

- **Active Record 模式**：Model 同时承载数据和操作
- **自动表名推断**：`User` → `users`，`PostCategory` → `post_categories`
- **批量赋值保护**：默认 `$guarded = ['*']`，需显式声明 `$fillable`
- **自动时间戳**：`created_at` / `updated_at` 自动维护
- **类型转换**：`$casts` 数组自动序列化/反序列化

---

## 2. 连接管理

### 2.1 Connection

```php
class Connection implements ConnectionInterface
{
    public function __construct(
        PDO $pdo,
        string $prefix = '',     // 表前缀
        string $driver = 'mysql', // 驱动类型
        ?string $name = null,     // 连接名
    ) {}
}
```

**核心方法**：

| 方法 | 说明 |
|------|------|
| `query($sql, $bindings)` | 执行查询，返回全部行 |
| `queryOne($sql, $bindings)` | 查询单行 |
| `execute($sql, $bindings)` | 执行写操作，返回 PDOStatement |
| `table($table)` | 获取 Query Builder |
| `insert($table, $data)` | 插入数据，返回 lastInsertId |
| `update($table, $data, $where, $bindings)` | 更新数据 |
| `delete($table, $where, $bindings)` | 删除数据 |
| `transaction($callback)` | 事务执行 |
| `getQueryCount()` | 查询计数 |
| `getQueries()` | 查询日志 |
| `getTotalQueryTime()` | 总查询时间 |

### 2.2 Manager — 多连接管理

```php
$manager = app(Manager::class);
$default = $manager->connection();          // 默认连接
$slave = $manager->connection('slave');     // 指定连接
```

### 2.3 DB Facade

```php
use Framework\Database\Facades\DB;

DB::table('users')->where('active', 1)->get();
DB::connection('slave')->table('users')->get();
```

---

## 3. Model — ORM 核心

### 3.1 基础定义

```php
use Framework\Database\Model;

class User extends Model
{
    protected string $table = 'users';          // 手动指定表名（可选，自动推断）
    protected string $primaryKey = 'id';        // 主键名
    protected array $fillable = ['name', 'email', 'password'];  // 可批量赋值字段
    protected array $guarded = ['*'];            // 不可批量赋值字段（与 fillable 互斥）
    protected array $hidden = ['password'];      // 序列化时隐藏
    protected array $casts = [                   // 类型转换
        'settings' => 'json',
        'is_admin' => 'boolean',
        'born_at' => 'datetime',
    ];
}
```

### 3.2 表名自动推断

| 类名 | 推断表名 |
|------|---------|
| `User` | `users` |
| `Post` | `posts` |
| `PostCategory` | `post_categories` |
| `AdminUserRole` | `admin_user_roles` |

规则：类名去掉 `Resource`/`Model` 后缀 → 蛇形命名 → 加 `s`

### 3.3 类型转换（Casts）

| cast 值 | 读取时 | 保存时 |
|---------|--------|--------|
| `int` / `integer` | `(int)` | 原值 |
| `float` / `double` | `(float)` | 原值 |
| `string` | `(string)` | 原值 |
| `bool` / `boolean` | `(bool)` | `1` / `0` |
| `array` | `json_decode()` | `json_encode()` |
| `json` | `json_decode()` | `json_encode()` |
| `datetime` | `new DateTime()` | `format('Y-m-d H:i:s')` |
| `timestamp` | `strtotime()` | 原值 |

### 3.4 CRUD 操作

#### 创建

```php
// 方式一：create（批量赋值）
$user = User::create(['name' => 'John', 'email' => 'john@example.com']);

// 方式二：实例化 + save
$user = new User();
$user->name = 'John';
$user->email = 'john@example.com';
$user->save();

// 方式三：fill + save
$user = new User();
$user->fill(['name' => 'John', 'email' => 'john@example.com']);
$user->save();
```

#### 查询

```php
// 基础查询
$user = User::find(1);                    // 按 ID 查找，返回 ?User
$user = User::findOrFail(1);              // 找不到抛 ModelNotFoundException
$users = User::all();                     // 全部记录

// 条件查询（返回 QueryBuilder）
$users = User::where('active', 1)->get();
$users = User::where('age', '>', 18)->get();
$user = User::where('email', 'john@example.com')->first();
```

#### 更新

```php
$user = User::find(1);
$user->name = 'Jane';
$user->save();                            // 只更新脏字段

// 批量更新
User::where('active', 0)->update(['active' => 1]);
```

#### 删除

```php
$user = User::find(1);
$user->delete();                          // 物理删除

// 按 ID 删除
User::destroy(1);

// 条件删除
User::where('active', 0)->delete();
```

### 3.5 脏字段追踪

```php
$user = User::find(1);
$user->name = 'New Name';

$user->isDirty();           // true — 有字段被修改
$user->isDirty('name');     // true — name 被修改
$user->isDirty('email');    // false — email 未修改
$user->getDirty();          // ['name' => 'New Name']
$user->isClean('email');    // true — email 未被修改
```

### 3.6 刷新与重建

```php
$user->fresh();             // 从数据库重新获取（新实例）
$user->refresh();           // 从数据库重新加载（当前实例）
```

### 3.7 序列化

```php
$user->toArray();           // 转数组（排除 hidden 字段，应用 casts）
$user->toJson();            // 转 JSON
```

### 3.8 批量赋值保护

```php
// 模式一：白名单（推荐）
protected array $fillable = ['name', 'email'];

// 模式二：黑名单
protected array $guarded = ['id', 'password'];

// 默认：全部禁止
protected array $guarded = ['*'];
```

**规则**：
- 当 `$guarded = ['*']` 时，只有 `$fillable` 中的字段可赋值
- 当 `$guarded` 非通配符时，不在 `$guarded` 中的字段可赋值
- 当 `$fillable` 为空且 `$guarded` 非通配符时，所有字段可赋值

---

## 4. Query Builder

### 4.1 获取 Builder

```php
// 通过 Model
$query = User::query();
$query = User::where('active', 1);

// 通过 Connection
$query = DB::table('users');
```

### 4.2 SELECT

```php
$query->select('id', 'name', 'email');
$query->addSelect('created_at');
$query->select('users.id', 'users.name as user_name');
$query->distinct();
```

### 4.3 WHERE

```php
// 基础
$query->where('name', 'John');
$query->where('age', '>', 18);
$query->orWhere('status', 'active');

// IN
$query->whereIn('id', [1, 2, 3]);
$query->whereNotIn('status', ['banned', 'suspended']);

// NULL
$query->whereNull('deleted_at');
$query->whereNotNull('email');

// LIKE
$query->whereLike('name', '%John%');

// BETWEEN
$query->whereBetween('age', 18, 65);
$query->whereNotBetween('price', 100, 200);

// 列比较
$query->whereColumn('created_at', '<', 'updated_at');

// EXISTS
$query->whereExists($subQuery);
$query->whereNotExists($subQuery);

// 日期
$query->whereDate('created_at', '>=', '2024-01-01');
$query->whereDay('created_at', '=', 15);
$query->whereMonth('created_at', '=', 6);
$query->whereYear('created_at', '=', 2024);

// 原始
$query->whereRaw('YEAR(created_at) = ?', [2024]);
```

### 4.4 JOIN

```php
$query->join('orders', 'users.id', '=', 'orders.user_id');
$query->leftJoin('profiles', 'users.id', '=', 'profiles.user_id');
$query->rightJoin('logs', 'users.id', '=', 'logs.user_id');
```

### 4.5 ORDER BY

```php
$query->orderBy('created_at', 'DESC');
$query->orderByDesc('id');
$query->orderByRaw('FIELD(status, "active", "pending", "inactive")');
$query->latest();                          // ORDER BY created_at DESC
$query->oldest();                          // ORDER BY created_at ASC
```

### 4.6 LIMIT / OFFSET

```php
$query->limit(10);
$query->offset(20);
```

### 4.7 GROUP BY / HAVING

```php
$query->groupBy('status');
$query->groupByRaw('YEAR(created_at)');
$query->having('count', '>', 10);
$query->havingRaw('COUNT(*) > ?', [5]);
```

### 4.8 聚合函数

```php
$query->count();
$query->sum('price');
$query->avg('rating');
$query->max('created_at');
$query->min('price');
$query->exists();
$query->doesntExist();
```

### 4.9 分页

```php
$result = $query->paginate(perPage: 15, page: 1);
// 返回:
// [
//     'data' => Collection,
//     'total' => 100,
//     'per_page' => 15,
//     'current_page' => 1,
//     'last_page' => 7,
//     'from' => 1,
//     'to' => 15,
// ]
```

### 4.10 写操作

```php
// 插入（返回 lastInsertId）
$id = DB::table('users')->insert(['name' => 'John', 'email' => 'john@example.com']);

// 更新（必须有 WHERE，返回影响行数）
$affected = DB::table('users')->where('id', 1)->update(['name' => 'Jane']);

// 删除（必须有 WHERE，返回影响行数）
$affected = DB::table('users')->where('id', 1)->delete();

// 自增/自减
DB::table('posts')->where('id', 1)->increment('view_count');
DB::table('posts')->where('id', 1)->increment('view_count', 5);
DB::table('products')->where('id', 1)->decrement('stock', 2);
```

### 4.11 锁

```php
$query->lockForUpdate();     // FOR UPDATE
$query->sharedLock();        // LOCK IN SHARE MODE
```

### 4.12 调试

```php
$sql = $query->toSql();          // 获取 SQL 字符串
$bindings = $query->getBindings(); // 获取绑定参数
```

### 4.13 安全限制

- `update()` 和 `delete()` **必须**有 WHERE 条件，否则抛 `RuntimeException`
- `increment()` / `decrement()` 同样必须有 WHERE 条件

---

## 5. 关系系统

### 5.1 关系类型总览

| 关系 | 方法 | 说明 | 示例 |
|------|------|------|------|
| HasOne | `hasOne()` | 一对一 | User → Profile |
| HasMany | `hasMany()` | 一对多 | User → Posts |
| BelongsTo | `belongsTo()` | 反向一对多 | Post → User |
| BelongsToMany | `belongsToMany()` | 多对多 | User ↔ Role |
| MorphMany | `morphMany()` | 多态一对多 | Comment → Post/Page |
| MorphTo | `morphTo()` | 多态反向 | Post/Page → Comment |

### 5.2 定义关系

```php
class User extends Model
{
    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
        // 默认: foreignKey = user_id, localKey = id
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
        // 默认: foreignKey = user_id, localKey = id
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
        // 默认: pivot表 = role_user, foreignPivotKey = user_id, relatedPivotKey = role_id
    }
}

class Post extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
        // 默认: foreignKey = user_id, ownerKey = id
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable_type', 'commentable_id');
    }
}
```

### 5.3 使用关系

```php
// 动态属性访问（自动加载）
$user = User::find(1);
$user->posts;          // 返回 Post[]
$user->profile;        // 返回 ?Profile

// 预加载（避免 N+1）
$users = User::with('posts', 'profile')->get();

// 延迟加载
$user->load('posts');
$user->loadMissing('posts');   // 只在未加载时才加载
```

### 5.4 BelongsToMany — 中间表操作

```php
$user = User::find(1);

// 附加
$user->roles()->attach($roleId);
$user->roles()->attach($roleId, ['expires_at' => '2024-12-31']);

// 分离
$user->roles()->detach($roleId);

// 同步（先全部删除再附加）
$user->roles()->sync([1, 2, 3]);
```

### 5.5 中间表命名规则

```
两个模型表名按字母排序后用下划线连接：
User + Role → role_user
Post + Tag  → post_tag
```

### 5.6 外键命名规则

```
HasMany/BelongsTo:
  User hasMany Post → posts.user_id
  Post belongsTo User → posts.user_id

BelongsToMany:
  User belongsToMany Role → role_user.user_id, role_user.role_id
```

---

## 6. Schema 与 Migration

### 6.1 Migration 基类

```php
use Framework\Database\Migration\Migration;

class CreateUsersTable extends Migration
{
    public function up(): void
    {
        $this->schema->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->boolean('is_admin')->default(false);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        $this->schema->drop('users');
    }
}
```

### 6.2 Blueprint 列类型

| 方法 | 数据库类型 | 说明 |
|------|-----------|------|
| `id($col)` | BIGINT UNSIGNED AI | 自增主键 |
| `bigIncrements($col)` | BIGINT UNSIGNED AI | 同 id() |
| `increments($col)` | INT UNSIGNED AI | 自增整型主键 |
| `uuid($col)` | CHAR(36) | UUID |
| `string($col, $len)` | VARCHAR($len) | 字符串，默认 255 |
| `text($col)` | TEXT | 长文本 |
| `mediumText($col)` | MEDIUMTEXT | 中等文本 |
| `longText($col)` | LONGTEXT | 超长文本 |
| `binary($col)` | BLOB | 二进制 |
| `integer($col)` | INT | 整型 |
| `bigInteger($col)` | BIGINT | 大整型 |
| `tinyInteger($col)` | TINYINT | 小整型 |
| `smallInteger($col)` | SMALLINT | 短整型 |
| `mediumInteger($col)` | MEDIUMINT | 中整型 |
| `unsignedInteger($col)` | INT UNSIGNED | 无符号整型 |
| `unsignedBigInteger($col)` | BIGINT UNSIGNED | 无符号大整型 |
| `boolean($col)` | TINYINT(1) | 布尔值，默认 0 |
| `decimal($col, $p, $s)` | DECIMAL($p,$s) | 精确小数 |
| `float($col, $p, $s)` | FLOAT | 浮点数 |
| `double($col, $p, $s)` | DOUBLE | 双精度 |
| `char($col, $len)` | CHAR($len) | 定长字符 |
| `date($col)` | DATE | 日期 |
| `time($col)` | TIME | 时间 |
| `datetime($col)` | DATETIME | 日期时间 |
| `timestamp($col)` | TIMESTAMP | 时间戳 |
| `year($col)` | YEAR | 年份 |
| `json($col)` | JSON | JSON 数据 |
| `enum($col, $values)` | ENUM | 枚举 |
| `set($col, $values)` | SET | 集合 |

### 6.3 Blueprint 修饰符

```php
$table->string('name')->nullable();          // 允许 NULL
$table->string('name')->default('John');     // 默认值
$table->integer('age')->unsigned();          // 无符号
$table->string('name')->comment('用户名');    // 注释
$table->string('name')->after('id');          // 列排序（MySQL）
$table->timestamp('created_at')->useCurrent(); // 默认 CURRENT_TIMESTAMP
```

### 6.4 Blueprint 索引

```php
$table->string('email')->unique();                    // 唯一索引
$table->unique(['email', 'tenant_id']);               // 复合唯一索引
$table->index('status');                               // 普通索引
$table->index(['status', 'created_at'], 'idx_status'); // 命名索引
```

### 6.5 Blueprint 特殊方法

```php
$table->timestamps();                      // created_at + updated_at
$table->softDeletes();                     // deleted_at
$table->softDeletes('removed_at');         // 自定义软删除列
$table->rememberToken();                   // remember_token VARCHAR(100)
```

### 6.6 外键

```php
$table->foreignId('user_id')->constrained();           // 外键引用 users.id
$table->foreignId('user_id')->constrained('admins');   // 外键引用 admins.id
$table->foreignId('user_id')->nullable()->constrained(); // 可空外键
```

### 6.7 Schema 操作

```php
// 创建表
$schema->create('users', function (Blueprint $table) { ... });

// 修改表
$schema->table('users', function (Blueprint $table) {
    $table->string('phone')->nullable()->after('email');
});

// 删除表
$schema->drop('users');
$schema->dropIfExists('users');

// 检查表/列是否存在
$schema->hasTable('users');     // bool
$schema->hasColumn('users', 'email');  // bool

// 重命名表
$schema->rename('users', 'members');

// 添加/删除列
$schema->addColumn('users', 'phone', 'VARCHAR(20)', ['nullable' => true]);
$schema->dropColumn('users', 'phone');
```

### 6.8 运行迁移

```bash
php bin/console migrate
```

---

## 7. Global Scopes

### 7.1 Scope 接口

```php
interface Scope
{
    public function apply(QueryBuilderInterface $query, Model $model): void;
}
```

### 7.2 SoftDeletingScope

```php
class SoftDeletingScope implements Scope
{
    public function apply(QueryBuilderInterface $query, Model $model): void
    {
        $column = method_exists($model, 'getDeletedAtColumn')
            ? $model->getDeletedAtColumn()
            : 'deleted_at';

        $query->whereNull($column);
    }
}
```

### 7.3 自定义 Scope

```php
class ActiveScope implements Scope
{
    public function apply(QueryBuilderInterface $query, Model $model): void
    {
        $query->where('active', 1);
    }
}

// 注册
User::addGlobalScope(new ActiveScope());

// 移除
User::removeGlobalScope(ActiveScope::class);
```

---

## 8. Trait 扩展

### 8.1 HasSoftDeletes

```php
class Post extends Model
{
    use HasSoftDeletes;
    // 自动注册 SoftDeletingScope
    // delete() → 设置 deleted_at（非物理删除）
}

// 使用
$post->delete();          // 软删除（设置 deleted_at）
$post->trashed();         // 是否已软删除
$post->restore();         // 恢复
$post->forceDelete();     // 物理删除

Post::withTrashed()->get();   // 包含软删除记录
Post::onlyTrashed()->get();   // 仅软删除记录
```

### 8.2 HasTranslations

```php
class Product extends Model
{
    use HasTranslations;

    protected array $casts = ['name' => 'json', 'description' => 'json'];
    protected array $translatable = ['name', 'description'];
}

// 写入
$product->setTranslation('name', 'en', 'Hello');
$product->setTranslation('name', 'zh', '你好');
$product->save();

// 批量设置
$product->setTranslations('name', ['en' => 'Hello', 'zh' => '你好']);

// 读取（自动使用当前 locale）
$product->name;                              // → 'Hello' (locale=en)

// 指定语言读取
$product->getTranslation('name', 'zh');      // → '你好'

// 获取所有翻译
$product->getTranslations('name');           // → ['en' => 'Hello', 'zh' => '你好']

// 删除翻译
$product->forgetTranslation('name', 'zh');
$product->forgetTranslations('name');        // 清空所有

// 检查翻译
$product->hasTranslation('name', 'zh');      // → bool

// 获取所有翻译属性
$product->getTranslatedAttributes('zh');      // → ['name' => '你好', 'description' => '...']
```

**数据库存储格式**：

```json
{"en": "Hello", "zh": "你好", "ja": "こんにちは"}
```

**回退机制**：当前语言 → `app.fallback_locale` → 第一个可用值

### 8.3 HasAuth

```php
class User extends Model
{
    use HasAuth;
}

// 密码验证
$user->verifyPassword('plain_password');     // → bool

// 密码哈希
$hash = User::hashPassword('plain_password');

// Token 管理
$token = $user->generateToken();             // 生成 remember_token
$user->clearToken();                         // 清除 token

// 查找
$user = User::findByEmail('john@example.com');
$user = User::findByToken($token);

// 认证接口
$user->getAuthIdentifier();                  // → id
$user->getAuthPassword();                    // → 哈希密码
$user->getRememberToken();                   // → token
$user->setRememberToken($token);
```

---

## 9. 事件与 Observer

### 9.1 模型事件

| 事件 | 常量 | 触发时机 | 可中断 |
|------|------|---------|--------|
| creating | `EVENT_CREATING` | 插入前 | ✅ |
| created | `EVENT_CREATED` | 插入后 | ❌ |
| updating | `EVENT_UPDATING` | 更新前 | ✅ |
| updated | `EVENT_UPDATED` | 更新后 | ❌ |
| saving | `EVENT_SAVING` | 保存前 | ✅ |
| saved | `EVENT_SAVED` | 保存后 | ❌ |
| deleting | `EVENT_DELETING` | 删除前 | ✅ |
| deleted | `EVENT_DELETED` | 删除后 | ❌ |
| retrieved | `EVENT_RETRIEVED` | 查询后 | ❌ |

### 9.2 注册事件监听

```php
// 静态注册
User::creating(function (User $user) {
    $user->password = User::hashPassword($user->password);
});

User::deleting(function (User $user) {
    // 返回 false 可中断删除
    if ($user->isSuperAdmin()) {
        return false;
    }
});

// 实例注册
$user->on('saving', function (User $user) {
    // ...
});
```

### 9.3 Observer

```php
class UserObserver
{
    public function creating(User $user): void
    {
        $user->password = User::hashPassword($user->password);
    }

    public function deleting(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return false;
        }
        return true;
    }
}

// 注册 Observer
User::observe(UserObserver::class);
```

### 9.4 事件触发流程

```
save()
  ├── EVENT_SAVING (可中断)
  │   ├── [新记录] EVENT_CREATING (可中断) → performInsert() → EVENT_CREATED
  │   └── [已有记录] EVENT_UPDATING (可中断) → performUpdate() → EVENT_UPDATED
  └── EVENT_SAVED
```

### 9.5 事件监听优先级

1. 静态监听器（`User::creating()`）
2. 实例监听器（`$user->on('creating')`）
3. 模型方法（`protected function creating()`）
4. Observer 方法

---

## 10. 完整 API 速查

### Model 静态方法

| 方法 | 说明 |
|------|------|
| `find($id)` | 按 ID 查找 |
| `findOrFail($id)` | 查找或抛异常 |
| `all()` | 获取全部 |
| `create($attrs)` | 创建并保存 |
| `where($col, $op, $val)` | 条件查询 |
| `query()` | 获取 Query Builder |
| `with(...$relations)` | 预加载 |
| `destroy($id)` | 按 ID 删除 |
| `addGlobalScope($scope)` | 添加全局作用域 |
| `observe($classes)` | 注册 Observer |
| `on($event, $callback)` | 注册事件 |
| `boot()` / `booted()` | 启动钩子 |

### Model 实例方法

| 方法 | 说明 |
|------|------|
| `save()` | 保存（插入或更新） |
| `delete()` | 删除 |
| `fill($attrs)` | 批量赋值 |
| `toArray()` | 转数组 |
| `toJson()` | 转 JSON |
| `isDirty($attr)` | 是否有修改 |
| `getDirty()` | 获取修改字段 |
| `fresh()` | 重新获取 |
| `refresh()` | 重新加载 |
| `load(...$relations)` | 延迟加载 |
| `loadMissing(...$relations)` | 缺失才加载 |
| `getAttribute($key)` | 获取属性 |
| `setAttribute($key, $val)` | 设置属性 |
| `getKey()` | 获取主键值 |
| `exists()` | 是否存在于数据库 |
