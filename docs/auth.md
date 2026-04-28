# 认证系统

## 概述

框架提供完整的用户认证系统，支持：
- Session 认证
- 密码哈希验证
- Remember Me (记住我)
- 角色与权限检查

## 配置

确保数据库中存在 `users` 表，可通过迁移创建：

```bash
php bin/console migrate
```

## 用户模型

```php
use Framework\Auth\User;

class User extends \Framework\Auth\User
{
    protected string $table = 'users';
    protected array $fillable = ['name', 'email', 'password'];
    protected array $hidden = ['password', 'remember_token'];
}
```

## 认证操作

### 登录验证

```php
use Framework\Auth\AuthManager;

$auth = $app->make(AuthManager::class);

// 尝试登录
if ($auth->attempt(['email' => $email, 'password' => $password])) {
    // 登录成功
}

// 带记住我
if ($auth->attempt($credentials, remember: true)) {
    // 登录成功，已设置记住我 Cookie
}
```

### 直接登录

```php
// 通过用户 ID 登录
$auth->loginUsingId(1);

// 直接登录用户对象
$user = User::find(1);
$auth->login($user);
```

### 检查认证状态

```php
// 是否已登录
if ($auth->check()) {
    // 已登录
}

// 是否访客
if ($auth->guest()) {
    // 未登录
}

// 获取当前用户 ID
$id = $auth->id();

// 获取当前用户模型
$user = $auth->user();
```

### 登出

```php
$auth->logout();
```

## 辅助函数

```php
// 获取 AuthManager 实例
auth();

// 获取当前用户
user();

// 示例
if (auth()->check()) {
    $name = user()->name;
}
```

## 密码处理

### 哈希密码

```php
$user = new User();
$hashedPassword = $user->hashPassword('plain-text-password');
```

### 验证密码

```php
$user = User::find(1);

if ($user->verifyPassword('input-password')) {
    // 密码正确
}
```

### 自动哈希

设置密码属性时自动哈希：

```php
$user = User::create([
    'name' => 'John',
    'email' => 'john@example.com',
    'password' => 'plain-text',  // 自动哈希
]);
```

## 角色与权限

用户模型可包含 `roles` 和 `permissions` 字段：

```php
// 检查角色
if (auth()->hasRole('admin')) {
    // 是管理员
}

// 检查权限
if (auth()->hasPermission('edit_posts')) {
    // 有编辑权限
}
```

用户数据结构示例：
```php
$user = [
    'id' => 1,
    'name' => 'John',
    'email' => 'john@example.com',
    'roles' => ['admin', 'editor'],
    'permissions' => ['edit_posts', 'delete_posts'],
];
```

## Remember Me

启用记住我后，系统会：
1. 生成随机 Token 存储到 `remember_token` 字段
2. 设置长期有效的 Cookie
3. 下次访问时自动通过 Token 恢复登录状态

```php
// 登录时启用
auth()->attempt($credentials, remember: true);

// 手动设置
$user->generateToken();
```

## 一次性登录

不修改 Session，仅验证一次：

```php
if (auth()->once($credentials)) {
    // 验证成功，但不会持久化登录状态
    $user = auth()->user();
}
```

## 自定义用户模型

```php
$auth->setUserModel(CustomUser::class);
```

## 完整示例

### 登录页面

```php
class LoginController
{
    public function store(Request $request)
    {
        $credentials = $request->only(['email', 'password']);
        
        if (auth()->attempt($credentials, $request->input('remember'))) {
            return redirect('/dashboard');
        }
        
        return redirect('/login')->with('error', 'Invalid credentials');
    }
}
```

### 受保护页面

```php
class DashboardController
{
    public function index()
    {
        if (auth()->guest()) {
            return redirect('/login');
        }
        
        return view('dashboard', ['user' => user()]);
    }
}
```

### 登出

```php
class LogoutController
{
    public function store()
    {
        auth()->logout();
        return redirect('/');
    }
}
```

## 数据库表结构

```php
// users 表迁移
$this->schema->create('users', function ($table) {
    $table->id();
    $table->string('name');
    $table->string('email')->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->string('remember_token', 100)->nullable();
    $table->timestamps();
});
```
