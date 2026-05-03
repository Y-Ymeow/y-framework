# Live 组件系统 — API 参考

> 由 DocGen 自动生成于 2026-05-03 15:49:15

## 目录

**其他**
- [`Computed`](#framework-component-live-attribute-computed)
- [`Cookie`](#framework-component-live-attribute-cookie)
- [`LiveAction`](#framework-component-live-attribute-liveaction)
- [`LiveComponent`](#framework-component-live-livecomponent)
- [`LiveComponentResolver`](#framework-component-live-livecomponentresolver)
- [`LiveEventBus`](#framework-component-live-liveeventbus)
- [`LiveListener`](#framework-component-live-attribute-livelistener)
- [`LiveNotifier`](#framework-component-live-livenotifier) — LiveNotifier 实时通知器
- [`LivePoll`](#framework-component-live-attribute-livepoll) — LivePoll — 标记可轮询的 LiveAction
- [`LiveSse`](#framework-component-live-attribute-livesse) — LiveSse — 标记返回 SSE 长连接响应的 LiveAction
- [`LiveStream`](#framework-component-live-attribute-livestream) — LiveStream — 标记返回流式响应的 LiveAction
- [`Prop`](#framework-component-live-attribute-prop)
- [`Rule`](#framework-component-live-attribute-rule)
- [`Session`](#framework-component-live-attribute-session)
- [`SseEndpoint`](#framework-component-live-sse-sseendpoint) — SSE Endpoint — 统一 SSE 入口
- [`SseHelper`](#framework-component-live-sse-ssehelper) — SSE 视图助手 — 在页面中注入 SSE 配置
- [`SseHub`](#framework-component-live-sse-ssehub) — SSE Hub — 中心化推送服务
- [`SseToken`](#framework-component-live-sse-ssetoken) — SSE 安全令牌
- [`StreamBuilder`](#framework-component-live-stream-streambuilder) — StreamBuilder 流式响应构建器

---

### 其他

<a name="framework-component-live-attribute-computed"></a>
#### `Framework\Component\Live\Attribute\Computed`

**文件:** `php/src/Component/Live/Attribute/Computed.php`

**属性：**

| 属性 | 类型 | 说明 |
|---|---|---|
| `$name` | `?string` |  |


<a name="framework-component-live-attribute-cookie"></a>
#### `Framework\Component\Live\Attribute\Cookie`

**文件:** `php/src/Component/Live/Attribute/Cookie.php`

**属性：**

| 属性 | 类型 | 说明 |
|---|---|---|
| `$key` | `?string` |  |
| `$minutes` | `int` |  |
| `$path` | `?string` |  |
| `$domain` | `?string` |  |
| `$secure` | `?bool` |  |
| `$httpOnly` | `bool` |  |


<a name="framework-component-live-attribute-liveaction"></a>
#### `Framework\Component\Live\Attribute\LiveAction`

**文件:** `php/src/Component/Live/Attribute/LiveAction.php`

**属性：**

| 属性 | 类型 | 说明 |
|---|---|---|
| `$name` | `?string` |  |


<a name="framework-component-live-livecomponent"></a>
#### `Framework\Component\Live\LiveComponent`

**实现:** `Stringable`  | **abstract**  | **文件:** `php/src/Component/Live/LiveComponent.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `setGlobalActionCache` |  | `array $cache` |
| `boot` | 生命周期：组件实例创建时触发 | — |
| `hydrate` | 生命周期：在状态从请求恢复（Hydration）完成后触发 | — |
| `dehydrate` | 生命周期：在状态序列化发往前端（Dehydration）开始前触发 | — |
| `param` | 获取路由参数值 | `string $key`, `mixed $default` = null |
| `params` | 获取所有路由参数 | — |
| `hasParam` | 判断是否存在指定路由参数 | `string $key` |
| `setRouteParams` | 设置路由参数（用于子请求或测试） | `array $params` |
| `updated` | 生命周期钩子：当任何公开属性更新后触发 | `string $name`, `mixed $value` |
| `render` |  | — |
| `getComponentId` |  | — |
| `named` | 设置组件 ID （用于唯一标识组件） 方便在更新时引用 | `string $id` |
| `toHtml` |  | `bool $onlyFragment` = false |
| `getLiveListeners` | 获取组件定义的监听器 | — |
| `getLivePolls` | 获取组件定义的轮询方法 | — |
| `serializeState` | 序列化状态 优化：只序列化非公开属性，公开属性由前端 JSON 维护，减少数据冗余 | — |
| `deserializeState` |  | `string $state` |
| `fillPublicProperties` | 将前端传来的公开属性值填充到组件中 | `array $data` |
| `getLiveActions` |  | — |
| `getLiveActionConfig` |  | `string $actionName` |
| `callAction` |  | `string $actionName`, `array $params` = [] |
| `signedAction` |  | `string $actionName`, `array $params` = [] |
| `getPublicProperties` |  | — |
| `getDataForFrontend` |  | — |
| `validate` |  | — |
| `emit` |  | `string $event`, `mixed $data` = null |
| `updateComponent` |  | `string $componentId`, `array $patches` = [] |
| `getManualUpdates` |  | — |
| `operation` |  | `string $op`, `array $params` = [] |
| `getOperations` |  | — |
| `redirect` |  | `string $url` |
| `refreshPage` |  | — |
| `dispatchEvent` |  | `string $event`, `array $detail` = [] |
| `ux` |  | `string $component`, `string $id`, `string $action`, `array $data` = [] |
| `openModal` |  | `string $id` |
| `closeModal` |  | `string $id` |
| `toggleAccordion` |  | `string $itemId`, `?bool $open` = null |
| `toast` |  | `string $message`, `string $type` = 'success', `int $duration` = 3000, `?string $title` = null |
| `confirm` | 触发确认对话框（前端显示） | `string $message`, `string $title` = '确认', `array $options` = [] |
| `loading` | 触发局部加载状态 | `string $target` = '' |
| `loadingEnd` | 结束加载状态 | `string $target` = '' |
| `validateForm` | 验证表单数据并返回错误信息 | `array $rules` = [], `array $data` = [] |
| `getError` | 获取表单错误信息 | `string $field` |
| `setError` | 设置表单错误信息 | `string $field`, `string $message` |
| `clearError` | 清除指定字段的错误 | `string $field` |
| `clearErrors` | 清除所有表单错误 | — |
| `notify` | 触发组件级刷新（通知外部组件更新） | `string $componentId`, `string $event`, `mixed $data` = null |
| `refresh` |  | `string $names` |
| `append` |  | `string $name` |
| `prepend` |  | `string $name` |
| `getRefreshFragments` |  | — |


<a name="framework-component-live-livecomponentresolver"></a>
#### `Framework\Component\Live\LiveComponentResolver`

**文件:** `php/src/Component/Live/LiveComponentResolver.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `handle` |  | `Framework\Http\Request $request` |
| `stream` |  | `Framework\Http\Request $request` |
| `navigate` |  | `Framework\Http\Request $request` |
| `intl` |  | `Framework\Http\Request $request` |


<a name="framework-component-live-liveeventbus"></a>
#### `Framework\Component\Live\LiveEventBus`

**文件:** `php/src/Component/Live/LiveEventBus.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `storeComponentState` |  | `string $componentId`, `string $class`, `string $state` |
| `getComponentState` |  | `string $componentId` |
| `getAllComponentStates` |  | — |
| `recordEmittedEvent` |  | `string $event`, `mixed $data` |
| `getEmittedEvents` |  | — |
| `reset` |  | — |
| `findListenersForEvent` |  | `string $event`, `string $excludeComponentId` = '' |


<a name="framework-component-live-attribute-livelistener"></a>
#### `Framework\Component\Live\Attribute\LiveListener`

**文件:** `php/src/Component/Live/Attribute/LiveListener.php`

**属性：**

| 属性 | 类型 | 说明 |
|---|---|---|
| `$event` | `string` |  |
| `$priority` | `int` |  |


<a name="framework-component-live-livenotifier"></a>
#### `Framework\Component\Live\LiveNotifier`

LiveNotifier 实时通知器

**文件:** `php/src/Component/Live/LiveNotifier.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `action` | 通过 SSE 触发组件 Action | `string $componentId`, `string $action`, `array $params` = [], `?string $channel` = null |
| `state` | 通过 SSE 直接更新组件属性 | `string $componentId`, `array $state`, `?string $channel` = null |
| `batch` | 通过 SSE 批量更新多个组件 | `array $updates`, `?string $channel` = null |
| `broadcast` | 通过 SSE 广播消息到频道 | `string $channel`, `array $message`, `string $event` = 'message' |
| `toUser` | 通过 SSE 推送消息给指定用户 | `int $userId`, `string $channel`, `array $message` |
| `emit` | 在当前请求周期内触发 LiveEventBus 事件 | `string $event`, `mixed $data` = null |


<a name="framework-component-live-attribute-livepoll"></a>
#### `Framework\Component\Live\Attribute\LivePoll`

LivePoll — 标记可轮询的 LiveAction

**文件:** `php/src/Component/Live/Attribute/LivePoll.php`

**属性：**

| 属性 | 类型 | 说明 |
|---|---|---|
| `$interval` | `int` |  |
| `$immediate` | `bool` |  |
| `$condition` | `string` |  |


<a name="framework-component-live-attribute-livesse"></a>
#### `Framework\Component\Live\Attribute\LiveSse`

LiveSse — 标记返回 SSE 长连接响应的 LiveAction

**文件:** `php/src/Component/Live/Attribute/LiveSse.php`

**属性：**

| 属性 | 类型 | 说明 |
|---|---|---|
| `$keepAlive` | `int` |  |
| `$channels` | `array` |  |


<a name="framework-component-live-attribute-livestream"></a>
#### `Framework\Component\Live\Attribute\LiveStream`

LiveStream — 标记返回流式响应的 LiveAction

**文件:** `php/src/Component/Live/Attribute/LiveStream.php`

**属性：**

| 属性 | 类型 | 说明 |
|---|---|---|
| `$format` | `string` |  |


<a name="framework-component-live-attribute-prop"></a>
#### `Framework\Component\Live\Attribute\Prop`

**文件:** `php/src/Component/Live/Attribute/Prop.php`

**属性：**

| 属性 | 类型 | 说明 |
|---|---|---|
| `$default` | `mixed` |  |


<a name="framework-component-live-attribute-rule"></a>
#### `Framework\Component\Live\Attribute\Rule`

**文件:** `php/src/Component/Live/Attribute/Rule.php`

**属性：**

| 属性 | 类型 | 说明 |
|---|---|---|
| `$rules` | `string` |  |


<a name="framework-component-live-attribute-session"></a>
#### `Framework\Component\Live\Attribute\Session`

**文件:** `php/src/Component/Live/Attribute/Session.php`

**属性：**

| 属性 | 类型 | 说明 |
|---|---|---|
| `$key` | `?string` |  |


<a name="framework-component-live-sse-sseendpoint"></a>
#### `Framework\Component\Live\Sse\SseEndpoint`

SSE Endpoint — 统一 SSE 入口

**文件:** `php/src/Component/Live/Sse/SseEndpoint.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `fromToken` | 从请求创建 Endpoint | `string $tokenString` |
| `keepAlive` | 设置心跳间隔 | `int $seconds` |
| `maxExecTime` | 设置最大执行时间 | `int $seconds` |
| `handle` | 处理 SSE 请求 | — |
| `generateClientConfig` | 生成前端初始化脚本 | — |
| `registerRoute` | 注册路由（在应用启动时调用） | — |


<a name="framework-component-live-sse-ssehelper"></a>
#### `Framework\Component\Live\Sse\SseHelper`

SSE 视图助手 — 在页面中注入 SSE 配置

**文件:** `php/src/Component/Live/Sse/SseHelper.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `metaElement` | 生成 SSE 配置 meta Element | `array $channels` = [] |
| `subscribe` | 在 Element 上添加 SSE 订阅 | `Framework\View\Base\Element $element`, `string $channels` |
| `tokenConfig` | 获取 SSE Token（用于 API 响应） | `array $channels` = [] |


<a name="framework-component-live-sse-ssehub"></a>
#### `Framework\Component\Live\Sse\SseHub`

SSE Hub — 中心化推送服务

**文件:** `php/src/Component/Live/Sse/SseHub.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `getInstance` | 获取单例实例 | — |
| `setCacheDir` | 设置缓存目录 | `string $dir` |
| `setMessageTtl` | 设置消息过期时间 | `int $ttl` |
| `push` | 推送消息到频道 | `string $channel`, `array $message`, `string $event` = 'message' |
| `toUser` | 推送消息给指定用户 | `int $userId`, `string $channel`, `array $message` |
| `liveAction` | 触发 LiveComponent 的 Action 更新 | `string $componentId`, `string $action`, `array $params` = [], `?string $channel` = null |
| `liveBatch` | 批量更新多个组件 | `array $updates`, `?string $channel` = null |
| `liveState` | 推送状态更新（直接更新组件属性） | `string $componentId`, `array $state`, `?string $channel` = null |
| `getMessages` | 获取频道的消息 | `string $channel`, `float $since` = 0, `?int $userId` = null |
| `getMessagesForChannels` | 获取多个频道的消息 | `array $channels`, `float $since` = 0, `?int $userId` = null |
| `cleanup` | 清理过期消息 | — |


<a name="framework-component-live-sse-ssetoken"></a>
#### `Framework\Component\Live\Sse\SseToken`

SSE 安全令牌

**文件:** `php/src/Component/Live/Sse/SseToken.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `setSecret` | 设置签名密钥（应用启动时调用） | `string $secret` |
| `setDefaultTtl` | 设置默认过期时间 | `int $ttl` |
| `generate` | 为当前会话生成 Token | `array $channels` = [], `int $ttl` = 0 |
| `parse` | 从字符串解析 Token | `string $tokenString` |
| `isValid` | 验证 Token 是否有效 | `bool $checkSession` = true |
| `canSubscribe` | 检查是否可以订阅指定频道 | `string $channel` |
| `canReceiveUserMessage` | 检查是否可以接收指定用户的消息 | `?int $targetUserId` |
| `toString` | 转换为字符串（用于传输） | — |
| `getId` |  | — |
| `getSessionId` |  | — |
| `getUserId` |  | — |
| `getChannels` |  | — |
| `getExpiresAt` |  | — |


<a name="framework-component-live-stream-streambuilder"></a>
#### `Framework\Component\Live\Stream\StreamBuilder`

StreamBuilder 流式响应构建器

**文件:** `php/src/Component/Live/Stream/StreamBuilder.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `create` | 创建构建器实例 | — |
| `format` | 设置输出格式 | `string $format` |
| `text` | 添加文本块 | `string $content`, `array $extra` = [] |
| `html` | 添加 HTML 块 | `string $html`, `array $extra` = [] |
| `json` | 添加 JSON 数据块 | `array $data`, `array $extra` = [] |
| `progress` | 添加进度更新 | `int $current`, `int $total`, `?string $message` = null |
| `error` | 添加错误块 | `string $message`, `int $code` = 0 |
| `done` | 添加完成标记 | `array $data` = [] |
| `thinking` | 添加 AI 思考状态块 | `string $thought` |
| `toolCall` | 添加工具调用块 | `string $tool`, `array $args` = [] |
| `each` | 延迟遍历可迭代对象（Generator 在 send() 时才执行） | `Traversable\|Generator\|array $items`, `callable $callback` |
| `raw` | 添加原始数据块 | `array $item` |
| `when` | 条件添加 | `bool $condition`, `callable $callback` |
| `build` | 构建 StreamResponse（延迟执行，Generator 在 send() 时才遍历） | — |
| `textChunk` | 创建文本块数据 | `string $content` |
| `progressChunk` | 创建进度块数据 | `int $current`, `int $total` |
| `doneChunk` | 创建完成块数据 | `array $data` = [] |
| `errorChunk` | 创建错误块数据 | `string $message`, `int $code` = 0 |


