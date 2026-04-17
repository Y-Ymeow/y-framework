# 数据库与映射器 (ORM)

本框架采用 Data Mapper 模式，配合 PHP 8 Attribute 实现极简的数据持久化。

## 定义实体 (Entity)

实体是一个纯粹的 POPO 类：

```php
use Framework\Database\Attributes\{Table, Column};

#[Table('users')]
class User {
    #[Column(isPrimary: true, autoIncrement: true)]
    public int $id;

    #[Column]
    public string $username;

    #[Column(name: 'user_email')]
    public string $email;
}
```

## 使用映射器 (Mapper)

```php
$mapper = app(Framework\Database\ORM\Mapper::class);

// 查找
$user = $mapper->find(User::class, 1);

// 修改并保存
$user->username = 'new_name';
$mapper->save($user);

// 新增
$newUser = new User();
$newUser->username = 'alice';
$mapper->save($newUser);
```

## 原生 SQL

我们依然鼓励在复杂查询时使用原生 SQL：

```php
$users = db()->select("SELECT * FROM users WHERE status = ?", [1]);
```

## 迁移 (Migrations)

使用 `bin/console db:migrate` 执行迁移。迁移文件位于 `database/migrations/`。
