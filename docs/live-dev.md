# Live 组件系统 — 开发文档

> 由 DocGen 自动生成于 2026-05-02 19:56:28

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
| `LivePoll` | `Framework\Component\Live\Attribute` | `php/src/Component/Live/Attribute/LivePoll.php` | class |
| `LiveSse` | `Framework\Component\Live\Attribute` | `php/src/Component/Live/Attribute/LiveSse.php` | class |
| `LiveStream` | `Framework\Component\Live\Attribute` | `php/src/Component/Live/Attribute/LiveStream.php` | class |
| `Prop` | `Framework\Component\Live\Attribute` | `php/src/Component/Live/Attribute/Prop.php` | class |
| `Rule` | `Framework\Component\Live\Attribute` | `php/src/Component/Live/Attribute/Rule.php` | class |
| `Session` | `Framework\Component\Live\Attribute` | `php/src/Component/Live/Attribute/Session.php` | class |
| `SseEndpoint` | `Framework\Component\Live\Sse` | `php/src/Component/Live/Sse/SseEndpoint.php` | class |
| `SseHelper` | `Framework\Component\Live\Sse` | `php/src/Component/Live/Sse/SseHelper.php` | class |
| `SseHub` | `Framework\Component\Live\Sse` | `php/src/Component/Live/Sse/SseHub.php` | class |
| `SseToken` | `Framework\Component\Live\Sse` | `php/src/Component/Live/Sse/SseToken.php` | class |
| `StreamBuilder` | `Framework\Component\Live\Stream` | `php/src/Component/Live/Stream/StreamBuilder.php` | class |

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

**公开方法 (39)：**

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
- `getLivePolls(): array` — 获取组件定义的轮询方法
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

**公开方法 (4)：**

- `handle(Framework\Http\Request $request): Framework\Http\Response`
- `stream(Framework\Http\Request $request): Framework\Http\Response`
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

### `Framework\Component\Live\Attribute\LivePoll`

- **文件:** `php/src/Component/Live/Attribute/LivePoll.php`

### `Framework\Component\Live\Attribute\LiveSse`

- **文件:** `php/src/Component/Live/Attribute/LiveSse.php`

### `Framework\Component\Live\Attribute\LiveStream`

- **文件:** `php/src/Component/Live/Attribute/LiveStream.php`

### `Framework\Component\Live\Attribute\Prop`

- **文件:** `php/src/Component/Live/Attribute/Prop.php`

### `Framework\Component\Live\Attribute\Rule`

- **文件:** `php/src/Component/Live/Attribute/Rule.php`

### `Framework\Component\Live\Attribute\Session`

- **文件:** `php/src/Component/Live/Attribute/Session.php`

### `Framework\Component\Live\Sse\SseEndpoint`

- **文件:** `php/src/Component/Live/Sse/SseEndpoint.php`

**公开方法 (6)：**

- `fromToken(string $tokenString): ?Framework\Component\Live\Sse\SseEndpoint` — 从请求创建 Endpoint
- `keepAlive(int $seconds): Framework\Component\Live\Sse\SseEndpoint` — 设置心跳间隔
- `maxExecTime(int $seconds): Framework\Component\Live\Sse\SseEndpoint` — 设置最大执行时间
- `handle(): void` — 处理 SSE 请求
- `generateClientConfig(): string` — 生成前端初始化脚本
- `registerRoute(): void` — 注册路由（在应用启动时调用）

### `Framework\Component\Live\Sse\SseHelper`

- **文件:** `php/src/Component/Live/Sse/SseHelper.php`

**公开方法 (3)：**

- `metaElement(array $channels = []): Framework\View\Base\Element` — 生成 SSE 配置 meta Element
- `subscribe(Framework\View\Base\Element $element, string $channels): Framework\View\Base\Element` — 在 Element 上添加 SSE 订阅
- `tokenConfig(array $channels = []): array` — 获取 SSE Token（用于 API 响应）

### `Framework\Component\Live\Sse\SseHub`

- **文件:** `php/src/Component/Live/Sse/SseHub.php`

**公开方法 (11)：**

- `getInstance(): Framework\Component\Live\Sse\SseHub` — 获取单例实例
- `setCacheDir(string $dir): void` — 设置缓存目录
- `setMessageTtl(int $ttl): void` — 设置消息过期时间
- `push(string $channel, array $message, string $event = 'message'): void` — 推送消息到频道
- `toUser(int $userId, string $channel, array $message): void` — 推送消息给指定用户
- `liveAction(string $componentId, string $action, array $params = [], ?string $channel = null): void` — 触发 LiveComponent 的 Action 更新
- `liveBatch(array $updates, ?string $channel = null): void` — 批量更新多个组件
- `liveState(string $componentId, array $state, ?string $channel = null): void` — 推送状态更新（直接更新组件属性）
- `getMessages(string $channel, float $since = 0, ?int $userId = null): array` — 获取频道的消息
- `getMessagesForChannels(array $channels, float $since = 0, ?int $userId = null): array` — 获取多个频道的消息
- `cleanup(): void` — 清理过期消息

### `Framework\Component\Live\Sse\SseToken`

- **文件:** `php/src/Component/Live/Sse/SseToken.php`

**公开方法 (13)：**

- `setSecret(string $secret): void` — 设置签名密钥（应用启动时调用）
- `setDefaultTtl(int $ttl): void` — 设置默认过期时间
- `generate(array $channels = [], int $ttl = 0): Framework\Component\Live\Sse\SseToken` — 为当前会话生成 Token
- `parse(string $tokenString): ?Framework\Component\Live\Sse\SseToken` — 从字符串解析 Token
- `isValid(bool $checkSession = true): bool` — 验证 Token 是否有效
- `canSubscribe(string $channel): bool` — 检查是否可以订阅指定频道
- `canReceiveUserMessage(?int $targetUserId): bool` — 检查是否可以接收指定用户的消息
- `toString(): string` — 转换为字符串（用于传输）
- `getId(): string`
- `getSessionId(): string`
- `getUserId(): ?int`
- `getChannels(): array`
- `getExpiresAt(): int`

### `Framework\Component\Live\Stream\StreamBuilder`

- **文件:** `php/src/Component/Live/Stream/StreamBuilder.php`

**公开方法 (18)：**

- `create(): Framework\Component\Live\Stream\StreamBuilder` — 创建构建器实例
- `format(string $format): Framework\Component\Live\Stream\StreamBuilder` — 设置输出格式
- `text(string $content, array $extra = []): Framework\Component\Live\Stream\StreamBuilder` — 添加文本块
- `html(string $html, array $extra = []): Framework\Component\Live\Stream\StreamBuilder` — 添加 HTML 块
- `json(array $data, array $extra = []): Framework\Component\Live\Stream\StreamBuilder` — 添加 JSON 数据块
- `progress(int $current, int $total, ?string $message = null): Framework\Component\Live\Stream\StreamBuilder` — 添加进度更新
- `error(string $message, int $code = 0): Framework\Component\Live\Stream\StreamBuilder` — 添加错误块
- `done(array $data = []): Framework\Component\Live\Stream\StreamBuilder` — 添加完成标记
- `thinking(string $thought): Framework\Component\Live\Stream\StreamBuilder` — 添加 AI 思考状态块
- `toolCall(string $tool, array $args = []): Framework\Component\Live\Stream\StreamBuilder` — 添加工具调用块
- `each(Traversable|Generator|array $items, callable $callback): Framework\Component\Live\Stream\StreamBuilder` — 延迟遍历可迭代对象（Generator 在 send() 时才执行）
- `raw(array $item): Framework\Component\Live\Stream\StreamBuilder` — 添加原始数据块
- `when(bool $condition, callable $callback): Framework\Component\Live\Stream\StreamBuilder` — 条件添加
- `build(): Framework\Http\StreamResponse` — 构建 StreamResponse（延迟执行，Generator 在 send() 时才遍历）
- `textChunk(string $content): array` — 创建文本块数据
- `progressChunk(int $current, int $total): array` — 创建进度块数据
- `doneChunk(array $data = []): array` — 创建完成块数据
- `errorChunk(string $message, int $code = 0): array` — 创建错误块数据

