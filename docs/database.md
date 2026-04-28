# 数据库系统

## 概述

框架提供完整的数据库抽象层，包括：
- **Connection**: PDO 连接管理
- **QueryBuilder**: 链式查询构建器
- **Model**: ORM 模型基类
- **Schema**: 表结构构建器
- **Migration**: 数据库迁移系统

## 配置

在 `config/database.php` 中配置数据库连接：

```php
return [
    'default' => env('DB_CONNECTION', 'mysql'),
    
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', 3306),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
        ],
        
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => storage_path('database.sqlite'),
        ],
    ],
];
```

## Query Builder

### 基本查询

```php
use Framework\Database\Connection;

$conn = $app->make(Connection::class);

// 原生查询
$users = $conn->query("SELECT * FROM users WHERE active = ?", [1]);

// 使用 QueryBuilder
$users = $conn->table('users')
    ->where('active', true)
    ->orderBy('created_at', 'DESC')
    ->get();

// 查询单条
$user = $conn->table('users')->where('id', 1)->first();

// 聚合函数
$count = $conn->table('users')->count();
$sum = $conn->table('orders')->sum('total');
$avg = $conn->table('products')->avg('price');
```

### 条件查询

```php
$conn->table('users')
    ->where('age', '>=', 18)
    ->where('status', 'active')
    ->orWhere('role', 'admin')
    ->get();

$conn->table('posts')
    ->whereIn('category_id', [1, 2, 3])
    ->whereNotNull('published_at')
    ->get();

$conn->table('users')
    ->whereLike('name', '%John%')
    ->get();

$conn->table('products')
    ->whereBetween('price', 100, 500)
    ->get();
```

### 关联查询

```php
$conn->table('orders')
    ->join('users', 'orders.user_id', '=', 'users.id')
    ->leftJoin('products', 'orders.product_id', '=', 'products.id')
    ->select('orders.*', 'users.name', 'products.title')
    ->get();
```

### 插入/更新/删除

```php
// 插入
$id = $conn->insert('users', [
    'name' => 'John',
    'email' => 'john@example.com',
]);

// 更新
$affected = $conn->update('users', 
    ['name' => 'Jane'], 
    'id = ?', 
    [1]
);

// 删除
$affected = $conn->delete('users', 'id = ?', [1]);

// 通过 QueryBuilder
$conn->table('users')
    ->where('id', 1)
    ->update(['name' => 'New Name']);

$conn->table('users')
    ->where('id', 1)
    ->delete();
```

### 分页

```php
$result = $conn->table('posts')
    ->orderBy('created_at', 'DESC')
    ->paginate(perPage: 15, page: 1);

// 返回结构
[
    'data' => [...],
    'total' => 100,
    'per_page' => 15,
    'current_page' => 1,
    'last_page' => 7,
    'from' => 1,
    'to' => 15,
]
```

### 事务

```php
$conn->transaction(function($conn) {
    $conn->insert('orders', ['user_id' => 1, 'total' => 100]);
    $conn->insert('order_items', ['order_id' => 1, 'product_id' => 5]);
});
```

## Model ORM

### 定义模型

```php
use Framework\Database\Model;

class User extends Model
{
    protected string $table = 'users';
    protected array $fillable = ['name', 'email', 'password'];
    protected array $hidden = ['password'];
    protected array $casts = [
        'is_admin' => 'bool',
        'settings' => 'array',
        'email_verified_at' => 'datetime',
    ];
}
```

### CRUD 操作

```php
// 创建
$user = User::create([
    'name' => 'John',
    'email' => 'john@example.com',
    'password' => 'hashed_password',
]);

// 查找
$user = User::find(1);
$user = User::findOrFail(1);  // 找不到抛出异常
$users = User::all();

// 查询
$users = User::where('is_admin', true)->get();
$user = User::where('email', 'john@example.com')->first();

// 更新
$user->name = 'Jane';
$user->save();

// 删除
$user->delete();
```

### 关联关系

```php
class User extends Model
{
    // 一对多
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
    
    // 一对一
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }
    
    // 属于
    public function role()
    {
        return $this->belongsTo(Role::class);
    }
    
    // 多对多
    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }
}

// 使用
$user->posts;        // 获取所有文章
$user->profile;      // 获取资料
$user->role;         // 获取角色
$user->tags;         // 获取标签

// 多对多操作
$user->tags()->attach(1);           // 添加标签
$user->tags()->detach(1);           // 移除标签
$user->tags()->sync([1, 2, 3]);     // 同步标签
```

### 类型转换

```php
protected array $casts = [
    'is_active' => 'bool',           // 布尔值
    'options' => 'array',            // JSON → 数组
    'price' => 'float',              // 浮点数
    'count' => 'int',                // 整数
    'created_at' => 'datetime',      // DateTime 对象
];
```

## Schema Builder

### 创建表

```php
use Framework\Database\Schema\Schema;

$schema->create('users', function($table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->string('password');
    $table->timestamp('email_verified_at')->nullable();
    $table->rememberToken();
    $table->timestamps();
    $table->softDeletes();
});
```

### 字段类型

| 方法 | 说明 |
|------|------|
| `id()` | 自增主键 (BIGINT UNSIGNED) |
| `string($column, $length)` | VARCHAR |
| `text($column)` | TEXT |
| `longText($column)` | LONGTEXT |
| `integer($column)` | INT |
| `bigInteger($column)` | BIGINT |
| `boolean($column)` | TINYINT(1) |
| `decimal($column, $precision, $scale)` | DECIMAL |
| `float($column, $precision, $scale)` | FLOAT |
| `date($column)` | DATE |
| `datetime($column)` | DATETIME |
| `timestamp($column)` | TIMESTAMP |
| `json($column)` | JSON |
| `enum($column, array $values)` | ENUM |
| `uuid($column)` | CHAR(36) |

### 字段修饰

```php
$table->string('nickname')->nullable();     // 允许 NULL
$table->integer('count')->default(0);       // 默认值
$table->bigInteger('user_id')->unsigned();  // 无符号
$table->string('email')->unique();          // 唯一索引
```

### 索引

```php
$table->string('email')->unique();    // 唯一索引
$table->string('name')->index();      // 普通索引
```

### 外键

```php
$table->foreign('user_id')
    ->references('id')
    ->on('users')
    ->onDelete('cascade')
    ->onUpdate('cascade');
```

### 其他操作

```php
$schema->drop('users');
$schema->dropIfExists('users');
$schema->rename('old_name', 'new_name');
$schema->hasTable('users');
$schema->hasColumn('users', 'email');
```

## Migration 迁移

### 创建迁移

```bash
php bin/console make:migration create_users_table
php bin/console make:migration add_status_to_posts_table posts
```

### 迁移文件结构

```php
<?php

namespace Database\Migrations;

use Framework\Database\Migration\Migration;

class CreateUsersTable extends Migration
{
    public function up(): void
    {
        $this->schema->create('users', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $this->schema->drop('users');
    }
}
```

### 运行迁移

```bash
# 执行所有待迁移
php bin/console migrate

# 重置数据库并重新迁移
php bin/console migrate --fresh

# 指定迁移数量
php bin/console migrate --step=1

# 回滚最后一次迁移
php bin/console migrate:rollback

# 回滚指定批次
php bin/console migrate:rollback --batch=3

# 回滚指定数量
php bin/console migrate:rollback --step=2
```

## 辅助函数

```php
db();  // 获取 Connection 实例

// 示例
$users = db()->table('users')->get();
```
