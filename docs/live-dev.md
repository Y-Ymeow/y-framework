# Live 组件系统 — 开发文档

> 由 DocGen 自动生成于 2026-05-02 05:37:00

## 组件清单

| 名称 | 命名空间 | 文件 | 类型 |
|---|---|---|---|
| `Computed` | `Framework\Component\Live\Attribute` | `php/src/Component/Live/Attribute/Computed.php` | class |
| `Cookie` | `Framework\Component\Live\Attribute` | `php/src/Component/Live/Attribute/Cookie.php` | class |
| `LiveAction` | `Framework\Component\Live\Attribute` | `php/src/Component/Live/Attribute/LiveAction.php` | class |
| `LiveComponent` | `Framework\Component\Live` | `php/src/Component/Live/LiveComponent.php` | abstract |
| `LiveComponentResolver` | `Framework\Component\Live` | `php/src/Component/Live/LiveComponentResolver.php` | class |
| `LiveEventBus` | `Framework\Component\Live` | `php/src/Component/Live/LiveEventBus.php` | class |
| `LiveListener` | `Framework\Component\Live\Attribute` | `php/src/Component/Live/Attribute/LiveListener.php` | class |
| `Prop` | `Framework\Component\Live\Attribute` | `php/src/Component/Live/Attribute/Prop.php` | class |
| `Rule` | `Framework\Component\Live\Attribute` | `php/src/Component/Live/Attribute/Rule.php` | class |
| `Session` | `Framework\Component\Live\Attribute` | `php/src/Component/Live/Attribute/Session.php` | class |

---

## 详细实现

### `Framework\Component\Live\Attribute\Computed`

- **文件:** `php/src/Component/Live/Attribute/Computed.php`

### `Framework\Component\Live\Attribute\Cookie`

- **文件:** `php/src/Component/Live/Attribute/Cookie.php`

### `Framework\Component\Live\Attribute\LiveAction`

- **文件:** `php/src/Component/Live/Attribute/LiveAction.php`

### `Framework\Component\Live\LiveComponent`

- **文件:** `php/src/Component/Live/LiveComponent.php`

**公开方法 (38)：**

- `setGlobalActionCache(array $cache): void`
- `boot(): void` — 生命周期：组件实例创建时触发
- `mount(): void` — 生命周期：仅在组件首次挂载时触发
- `hydrate(): void` — 生命周期：在状态从请求恢复（Hydration）完成后触发
- `dehydrate(): void` — 生命周期：在状态序列化发往前端（Dehydration）开始前触发
- `updated(string $name, mixed $value): void` — 生命周期钩子：当任何公开属性更新后触发
- `render(): void`
- `getComponentId(): string`
- `named(string $id): Framework\Component\Live\LiveComponent`
- `toHtml(bool $onlyFragment = false): string`
- `getLiveListeners(): array` — 获取组件定义的监听器
- `serializeState(): string` — 序列化状态 优化：只序列化非公开属性，公开属性由前端 JSON 维护，减少数据冗余
- `deserializeState(string $state): void`
- `fillPublicProperties(array $data): void` — 将前端传来的公开属性值填充到组件中
- `getLiveActions(): array`
- `getLiveActionConfig(string $actionName): ?array`
- `callAction(string $actionName, array $params = []): mixed`
- `signedAction(string $actionName, array $params = []): string`
- `getPublicProperties(): array`
- `getDataForFrontend(): array`
- `validate(): array`
- `emit(string $event, mixed $data = null): void`
- `updateComponent(string $componentId, array $patches = []): void`
- `getManualUpdates(): array`
- `operation(string $op, array $params = []): void`
- `getOperations(): array`
- `redirect(string $url): void`
- `refreshPage(): void`
- `dispatchEvent(string $event, array $detail = []): void`
- `ux(string $component, string $id, string $action, array $data = []): void`
- `openModal(string $id): void`
- `closeModal(?string $id = null): void`
- `toggleAccordion(string $itemId, ?bool $open = null): void`
- `toast(string $message, string $type = 'success', int $duration = 3000, ?string $title = null): void`
- `refresh(string $names): void`
- `append(string $name): void`
- `prepend(string $name): void`
- `getRefreshFragments(): array`

### `Framework\Component\Live\LiveComponentResolver`

- **文件:** `php/src/Component/Live/LiveComponentResolver.php`

**公开方法 (3)：**

- `handle(Framework\Http\Request $request): Framework\Http\Response`
- `navigate(Framework\Http\Request $request): Framework\Http\Response`
- `intl(Framework\Http\Request $request): Framework\Http\Response`

### `Framework\Component\Live\LiveEventBus`

- **文件:** `php/src/Component/Live/LiveEventBus.php`

**公开方法 (7)：**

- `storeComponentState(string $componentId, string $class, string $state): void`
- `getComponentState(string $componentId): ?array`
- `getAllComponentStates(): array`
- `recordEmittedEvent(string $event, mixed $data): void`
- `getEmittedEvents(): array`
- `reset(): void`
- `findListenersForEvent(string $event, string $excludeComponentId = ''): array`

### `Framework\Component\Live\Attribute\LiveListener`

- **文件:** `php/src/Component/Live/Attribute/LiveListener.php`

### `Framework\Component\Live\Attribute\Prop`

- **文件:** `php/src/Component/Live/Attribute/Prop.php`

### `Framework\Component\Live\Attribute\Rule`

- **文件:** `php/src/Component/Live/Attribute/Rule.php`

### `Framework\Component\Live\Attribute\Session`

- **文件:** `php/src/Component/Live/Attribute/Session.php`

