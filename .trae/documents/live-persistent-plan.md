# Live 组件持久化方案

## 问题分析

现有持久化方案的不足：
1. **Session**：依赖服务器端存储，在分布式环境下需要共享 session，且数据生命周期受 session 配置限制
2. **Cookie**：数据量限制（~4KB），每次请求都会携带，影响性能
3. **前端序列化状态**：页面刷新后丢失，不能跨页面/跨会话保持

## 解决方案

设计一个多层次的持久化方案，通过属性注解驱动，支持多种存储后端。

## 实施步骤

### 步骤 1：创建持久化属性注解

创建 `#[Persistent]` 属性，支持多种存储驱动：
- `local` - 浏览器 LocalStorage（前端）
- `session` - 浏览器 SessionStorage（前端）
- `cookie` - Cookie（前端）
- `database` - 数据库（后端）
- `cache` - 缓存（后端）
- `redis` - Redis（后端）

### 步骤 2：创建持久化管理器

实现 `PersistentStateManager` 类：
- 统一处理各种存储后端的读写
- 支持序列化/反序列化
- 支持过期时间管理
- 提供缓存优化

### 步骤 3：扩展 LiveComponent 基类

修改 `LiveComponent` 的 `syncPersistentAttributes()` 和 `updated()` 方法：
- 在组件初始化时从持久化存储恢复状态
- 在属性更新时自动保存到持久化存储
- 保持向后兼容现有的 `#[Session]` 和 `#[Cookie]`

### 步骤 4：前端 JS 支持

创建 `y-live/persistent.js`：
- 处理 `local` 和 `session` 类型的持久化
- 在组件渲染时同步数据
- 监听属性变化并保存

### 步骤 5：数据库持久化驱动

创建 `DatabasePersistentDriver`：
- 使用数据库表存储组件状态
- 支持用户关联（按 user_id 分组）
- 支持过期时间

### 步骤 6：Redis 持久化驱动

创建 `RedisPersistentDriver`：
- 高性能的持久化存储
- 支持 TTL 自动过期

### 步骤 7：更新 LanguageSwitcher 使用新的持久化方案

将语言选择改为使用 `#[Persistent('local')]`，这样用户的语言偏好会持久保存在浏览器中，即使关闭页面再打开也能记住选择。

## 技术细节

### 属性定义

```php
#[Persistent(
    driver: 'local',  // local/session/cookie/database/cache/redis
    key: null,        // 自定义键名，默认使用 类名.属性名
    ttl: null,        // 过期时间（秒），null 表示永久
    encrypt: false    // 是否加密存储
)]
public string $locale = 'en';
```

### 数据库表设计

```sql
CREATE TABLE component_states (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id VARCHAR(255) NULL,
    session_id VARCHAR(255) NULL,
    component_class VARCHAR(255) NOT NULL,
    property_name VARCHAR(255) NOT NULL,
    storage_key VARCHAR(500) NOT NULL,
    value TEXT NOT NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_storage_key (storage_key),
    INDEX idx_user_id (user_id),
    INDEX idx_expires_at (expires_at)
);
```

## 使用示例

```php
class LanguageSwitcherLive extends LiveComponent
{
    #[Persistent('local')]
    public string $locale = 'en';
    
    #[Persistent('local', ttl: 86400 * 30)]
    public array $preferences = [];
    
    #[Persistent('database')]
    public array $userSettings = [];
}
```
