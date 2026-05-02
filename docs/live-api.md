# Live 组件系统 — API 参考

> 由 DocGen 自动生成于 2026-05-02 05:56:00

## 目录

**其他**
- [`Computed`](#framework-component-live-attribute-computed)
- [`Cookie`](#framework-component-live-attribute-cookie)
- [`LiveAction`](#framework-component-live-attribute-liveaction)
- [`LiveComponent`](#framework-component-live-livecomponent)
- [`LiveComponentResolver`](#framework-component-live-livecomponentresolver)
- [`LiveEventBus`](#framework-component-live-liveeventbus)
- [`LiveListener`](#framework-component-live-attribute-livelistener)
- [`Prop`](#framework-component-live-attribute-prop)
- [`Rule`](#framework-component-live-attribute-rule)
- [`Session`](#framework-component-live-attribute-session)

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
| `mount` | 生命周期：仅在组件首次挂载时触发 | — |
| `hydrate` | 生命周期：在状态从请求恢复（Hydration）完成后触发 | — |
| `dehydrate` | 生命周期：在状态序列化发往前端（Dehydration）开始前触发 | — |
| `updated` | 生命周期钩子：当任何公开属性更新后触发 | `string $name`, `mixed $value` |
| `render` |  | — |
| `getComponentId` |  | — |
| `named` |  | `string $id` |
| `toHtml` |  | `bool $onlyFragment` = false |
| `getLiveListeners` | 获取组件定义的监听器 | — |
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
| `closeModal` |  | `?string $id` = null |
| `toggleAccordion` |  | `string $itemId`, `?bool $open` = null |
| `toast` |  | `string $message`, `string $type` = 'success', `int $duration` = 3000, `?string $title` = null |
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


