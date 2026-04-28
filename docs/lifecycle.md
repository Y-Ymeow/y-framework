# 生命周期与 Hook 系统 (Lifecycle & Hook System)

## 概述

生命周期管理系统提供统一的事件监听、组件收集和资源注册机制。通过 Hook 系统，开发者可以在框架运行的关键节点注入逻辑，实现高度解耦的插件化开发。

## 1. Hook 系统

采用类似 WordPress 的 Action/Filter 机制。

### HookListener - 事件监听

```php
use Framework\Events\Attribute\HookListener;

class AppBootListener
{
    #[HookListener('app.booted')]
    public function onAppBooted(): void
    {
        // 应用启动后执行
    }

    #[HookListener('response.created', priority: 20, acceptedArgs: 2)]
    public function afterResponseCreated($response, $request): void
    {
        // 接收多个参数
    }
}
```

### HookFilter - 数据过滤

```php
use Framework\Events\Attribute\HookFilter;

class OutputFilter
{
    #[HookFilter('response.sending')]
    public function minifyHtml($response): mixed
    {
        // 修改响应内容
        return $response;
    }
}
```

### 手动触发与绑定

```php
// 触发 Action
fire('user.created', $user);

// 应用 Filter
$value = filter('my.data', $initialValue);

// 手动绑定
hook('app.booted', function() { ... });
```

## 2. 全局生命周期 Hook 列表

| Hook 名称 | 类型 | 触发时机 | 参数 |
|-----------|------|----------|------|
| `app.booting` | Action | 服务提供者注册后，应用启动前 | 无 |
| `app.booted` | Action | 应用完全启动后 | 无 |
| `request.received` | Action | 收到请求后，分发前 | `$request` |
| `response.created` | Action | 路由分发产生响应对象后 | `$response, $request` |
| `response.sending` | Filter | 响应发送前 | `$response, $request` |
| `response.sent` | Action | 响应发送后 | `$response, $request` |

## 3. 收集器 (Collectors)

`LifecycleManager` 负责管理各类资源的收集，如路由、组件和服务。

### 路由收集

通过 `#[Route]`、`#[Get]` 等 Attribute 自动扫描。

### 组件收集

通过 `#[LiveListener]` Attribute 自动扫描。

## 4. 架构设计

- **`Hook`**: 核心事件引擎，支持静态和单例调用。
- **`LifecycleManager`**: 管理收集器和生命周期状态。
- **`AttributeScanner`**: 负责扫描类并解析 Attribute。
- **`Kernel`**: 驱动整个生命周期，并在关键节点触发 Hook。

## 5. DebugBar 集成示例

DebugBar 现在完全通过 Hook 系统集成，不再硬编码在 `Kernel` 中：

1. `DebugBarServiceProvider` 注册 `DebugBarListener`。
2. `DebugBarListener` 监听 `response.created` 收集数据。
3. `DebugBarListener` 监听 `response.sending` 注入 HTML。
