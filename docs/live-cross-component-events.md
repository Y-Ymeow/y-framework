# Live 组件跨组件事件通信系统

## 概述

Live 组件系统现在支持**跨组件事件通信**，允许在一个 HTTP 请求内完成多个组件的状态同步更新。这类似于 Livewire 的 `dispatch` 机制，但具有更好的性能和更清晰的设计。

## 核心特性

- ✅ **单请求多组件更新**：一个请求内完成所有受影响组件的状态同步
- ✅ **声明式事件监听**：使用 `#[LiveListener]` 注解标记事件监听器
- ✅ **自动组件发现**：服务端自动扫描并实例化所有监听该事件的组件
- ✅ **状态签名验证**：保持原有的 HMAC-SHA256 签名验证机制
- ✅ **分片精准刷新**：每个组件的 fragments 独立管理和刷新
- ✅ **响应式状态同步**：前端通过 `_y_state` 自动合并 patches

## 快速开始

### 1. 定义事件监听器

在组件方法上使用 `#[LiveListener]` 注解：

```php
class StatsComponent extends LiveComponent
{
    public int $totalEvents = 0;
    public int $maxValue = 0;

    // 监听 counter:incremented 事件
    #[LiveListener('counter:incremented')]
    public function onCounterIncremented(?array $data = null): void
    {
        $this->totalEvents++;
        if ($data['count'] > $this->maxValue) {
            $this->maxValue = $data['count'];
        }
        $this->refresh('stats-display');
    }
}
```

### 2. 发送事件

在组件的 `#[LiveAction]` 方法中调用 `emit()`：

```php
class CounterComponent extends LiveComponent
{
    public int $count = 0;

    #[LiveAction]
    public function increment(): void
    {
        $this->count++;
        
        // 发出事件，传递数据
        $this->emit('counter:incremented', [
            'count' => $this->count,
            'timestamp' => time(),
        ]);
        
        $this->refresh('counter-display');
    }
}
```

### 3. 页面渲染

在页面中同时渲染多个组件：

```php
public function render()
{
    $counter = new CounterComponent()->named('counter-1');
    $stats = new StatsComponent()->named('stats-1');

    return Container::make()->children(
        $counter,
        $stats,
    );
}
```

## 工作原理

### 前端流程

```
1. 用户触发 Counter 的 increment 按钮
   ↓
2. Y.dispatchLive() 收集页面上所有 Live 组件的状态
   {
     _components: [
       { id: 'counter-1', class: 'CounterComponent', state: '...' },
       { id: 'stats-1', class: 'StatsComponent', state: '...' }
     ]
   }
   ↓
3. 发送 POST /live 请求
```

### 服务端流程

```
1. LiveComponentResolver 接收请求
   ↓
2. 将所有组件状态存储到 LiveEventBus
   ↓
3. 实例化触发组件 (Counter)，恢复状态
   ↓
4. 执行 increment action
   ↓
5. Counter 调用 emit('counter:incremented', data)
   ↓
6. LiveEventBus 记录事件
   ↓
7. 扫描所有组件的 #[LiveListener] 注解
   → 找到 Stats.onCounterIncremented
   ↓
8. 实例化 Stats，恢复状态
   ↓
9. 调用 Stats.onCounterIncremented(data)
   ↓
10. 收集所有组件的更新：
    {
      state: "Counter新state",
      patches: {Counter的变化},
      componentUpdates: [
        {
          componentId: 'stats-1',
          state: "Stats新state",
          patches: {Stats的变化},
          fragments: [Stats的分片]
        }
      ]
    }
```

### 前端更新流程

```javascript
function applyLiveResponse(el, data, state, stateRef, componentId) {
    // 更新主组件
    if (data.state) {
        el.setAttribute('data-live-state', data.state);
        if (stateRef) stateRef.value = data.state;
    }

    // patches 更新（自动同步 data-state）
    if (data.patches) {
        batch(() => {
            state.merge(data.patches);
        });
    }

    // 处理跨组件更新
    if (data.componentUpdates) {
        data.componentUpdates.forEach(update => {
            const targetEl = document.querySelector(`[data-live-id="${update.componentId}"]`);
            if (!targetEl) return;

            // 更新签名状态
            if (update.state) {
                targetEl.setAttribute('data-live-state', update.state);
            }

            // patches 更新（自动同步 data-state）
            if (update.patches && targetEl._y_state) {
                batch(() => {
                    targetEl._y_state.merge(update.patches);
                });
            }

            // 刷新分片
            if (update.fragments) {
                update.fragments.forEach(fragment => {
                    applyLiveFragment(targetEl, fragment, update.state);
                });
            }
        });
    }

    // 执行操作
    if (data.operations) {
        data.operations.forEach(op => Y.executeOperation(op));
    }
}
```

## API 参考

### `#[LiveListener]` 属性

标记一个方法为事件监听器。

```php
#[Attribute(\Attribute::TARGET_METHOD)]
class LiveListener
{
    public function __construct(
        public string $event,    // 事件名称
        public int $priority = 0 // 优先级（暂未使用）
    ) {}
}
```

**用法：**
```php
#[LiveListener('user:created')]
public function onUserCreated(?array $data = null): void
{
    // 处理事件
}
```

### `emit()` 方法

发出一个事件，触发所有监听该事件的组件。

```php
public function emit(string $event, mixed $data = null): void
```

**参数：**
- `$event`: 事件名称（格式建议：`模块:操作`，如 `counter:incremented`）
- `$data`: 传递给监听者的数据（数组或任意类型）

**用法：**
```php
$this->emit('counter:incremented', [
    'count' => $this->count,
    'timestamp' => time(),
]);
```

### `refresh()` 方法

标记需要刷新的分片。

```php
public function refresh(string ...$names): void
```

**用法：**
```php
$this->refresh('stats-display', 'event-log');
```

## 完整示例

### 计数器组件

```php
<?php

namespace App\Components;

use Framework\Component\Attribute\LiveAction;
use Framework\Component\Attribute\LiveListener;
use Framework\Component\LiveComponent;
use Framework\View\Base\Element;
use Framework\View\Container;
use Framework\View\Text;

class CounterComponent extends LiveComponent
{
    public int $count = 0;
    public int $totalOperations = 0;
    public string $lastAction = '';

    #[LiveAction]
    public function increment(): void
    {
        $this->count++;
        $this->totalOperations++;
        $this->lastAction = 'increment';
        
        // 发出事件，通知其他组件
        $this->emit('counter:incremented', [
            'count' => $this->count,
            'timestamp' => time(),
        ]);
        
        $this->refresh('counter-display', 'counter-info');
    }

    #[LiveAction]
    public function decrement(): void
    {
        $this->count--;
        $this->totalOperations++;
        $this->lastAction = 'decrement';
        
        $this->emit('counter:decremented', [
            'count' => $this->count,
            'timestamp' => time(),
        ]);
        
        $this->refresh('counter-display', 'counter-info');
    }

    #[LiveAction]
    public function reset(): void
    {
        $this->count = 0;
        $this->totalOperations++;
        $this->lastAction = 'reset';
        
        $this->emit('counter:reset', [
            'count' => $this->count,
            'timestamp' => time(),
        ]);
        
        $this->refresh('counter-display', 'counter-info');
    }

    // 监听其他组件的事件
    #[LiveListener('stats:cleared')]
    public function onStatsCleared(?array $data = null): void
    {
        $this->count = 0;
        $this->lastAction = 'stats-cleared';
        $this->refresh('counter-display', 'counter-info');
    }

    public function render(): string|Element
    {
        return Container::make()->children(
            Element::make('div')
                ->id('counter-display')
                ->class('text-5xl font-bold text-center mb-6')
                ->liveFragment('counter-display')
                ->text((string) $this->count),

            Container::make()
                ->class('flex gap-3 mb-4')
                ->children(
                    Element::make('button')
                        ->class('px-6 py-3 bg-green-500 text-white rounded-lg')
                        ->liveAction('increment')
                        ->text('+1'),

                    Element::make('button')
                        ->class('px-6 py-3 bg-red-500 text-white rounded-lg')
                        ->liveAction('decrement')
                        ->text('-1'),

                    Element::make('button')
                        ->class('px-6 py-3 bg-gray-500 text-white rounded-lg')
                        ->liveAction('reset')
                        ->text('重置')
                ),

            Element::make('div')
                ->id('counter-info')
                ->class('mt-4 p-4 bg-gray-50 rounded-lg')
                ->liveFragment('counter-info')
                ->children(
                    Text::p('操作次数: ' . $this->totalOperations),
                    Text::p('上次操作: ' . $this->lastAction),
                )
        );
    }
}
```

### 统计组件

```php
<?php

namespace App\Components;

use Framework\Component\Attribute\LiveAction;
use Framework\Component\Attribute\LiveListener;
use Framework\Component\LiveComponent;
use Framework\View\Base\Element;
use Framework\View\Container;
use Framework\View\Text;

class StatsComponent extends LiveComponent
{
    public int $maxValue = 0;
    public int $minValue = 0;
    public int $totalEvents = 0;
    public array $eventLog = [];

    #[LiveListener('counter:incremented')]
    public function onCounterIncremented(?array $data = null): void
    {
        $this->totalEvents++;
        
        if ($data['count'] > $this->maxValue) {
            $this->maxValue = $data['count'];
        }
        
        $this->eventLog[] = [
            'type' => 'increment',
            'count' => $data['count'],
            'time' => date('H:i:s', $data['timestamp']),
        ];
        
        if (count($this->eventLog) > 5) {
            array_shift($this->eventLog);
        }
        
        $this->refresh('stats-display', 'event-log');
    }

    #[LiveListener('counter:decremented')]
    public function onCounterDecremented(?array $data = null): void
    {
        $this->totalEvents++;
        
        if ($this->minValue === 0 || $data['count'] < $this->minValue) {
            $this->minValue = $data['count'];
        }
        
        $this->eventLog[] = [
            'type' => 'decrement',
            'count' => $data['count'],
            'time' => date('H:i:s', $data['timestamp']),
        ];
        
        if (count($this->eventLog) > 5) {
            array_shift($this->eventLog);
        }
        
        $this->refresh('stats-display', 'event-log');
    }

    #[LiveListener('counter:reset')]
    public function onCounterReset(?array $data = null): void
    {
        $this->totalEvents++;
        $this->eventLog[] = [
            'type' => 'reset',
            'count' => $data['count'],
            'time' => date('H:i:s', $data['timestamp']),
        ];
        
        if (count($this->eventLog) > 5) {
            array_shift($this->eventLog);
        }
        
        $this->refresh('stats-display', 'event-log');
    }

    #[LiveAction]
    public function clearStats(): void
    {
        $this->maxValue = 0;
        $this->minValue = 0;
        $this->totalEvents = 0;
        $this->eventLog = [];
        
        // 发出事件，通知计数器重置
        $this->emit('stats:cleared', [
            'cleared_at' => time(),
        ]);
        
        $this->refresh('stats-display', 'event-log');
    }

    public function render(): string|Element
    {
        $logItems = array_map(function($log) {
            return Text::p("{$log['time']} - {$log['type']} (值: {$log['count']})");
        }, $this->eventLog);

        return Container::make()->children(
            Element::make('div')
                ->id('stats-display')
                ->class('mb-4 space-y-3')
                ->liveFragment('stats-display')
                ->children(
                    $this->createStatCard('最大值', (string) $this->maxValue),
                    $this->createStatCard('最小值', (string) $this->minValue),
                    $this->createStatCard('事件总数', (string) $this->totalEvents),
                ),

            Element::make('button')
                ->class('w-full px-4 py-2 bg-orange-500 text-white rounded-lg')
                ->liveAction('clearStats')
                ->text('清除所有统计'),

            Element::make('div')
                ->id('event-log')
                ->class('p-4 bg-gray-50 rounded-lg')
                ->liveFragment('event-log')
                ->children(
                    Text::h3('事件日志'),
                    ...$logItems
                )
        );
    }

    private function createStatCard(string $label, string $value): Element
    {
        return Container::make()
            ->class('p-4 rounded-lg border bg-white')
            ->children(
                Text::p($label)->class('text-xs font-medium opacity-75'),
                Text::p($value)->class('text-2xl font-bold mt-1'),
            );
    }
}
```

## 响应格式

### 成功响应

```json
{
  "success": true,
  "component": "App\\Components\\CounterComponent",
  "action": "increment",
  "state": "base64_encoded_state",
  "patches": {
    "count": 5,
    "totalOperations": 5,
    "lastAction": "increment"
  },
  "domPatches": [],
  "fragments": [
    {
      "name": "counter-display",
      "html": "<div>5</div>",
      "mode": "replace"
    }
  ],
  "operations": [],
  "componentUpdates": [
    {
      "componentId": "stats-1",
      "state": "base64_encoded_stats_state",
      "patches": {
        "totalEvents": 1,
        "maxValue": 5,
        "eventLog": [...]
      },
      "fragments": [
        {
          "name": "stats-display",
          "html": "...",
          "mode": "replace"
        }
      ]
    }
  ]
}
```

## 技术实现

### 核心类

- `LiveComponent`: 组件基类，提供 `emit()` 和 `refresh()` 方法
- `LiveComponentResolver`: HTTP 请求处理器，负责实例化和调度组件
- `LiveEventBus`: 事件总线，存储组件状态和事件记录
- `LiveListener`: 事件监听器注解
- `Y.dispatchLive()`: 前端发送请求函数
- `Y.applyLiveResponse()`: 前端处理响应函数

### 状态管理

- **data-live-state**: 签名状态（二进制压缩 + HMAC-SHA256）
- **data-state**: 前端可见状态（JSON 格式，由 patches 更新）
- **patches**: 状态变化量，用于响应式更新并同步 data-state

### 安全机制

- 所有状态传递都经过 HMAC-SHA256 签名验证
- Action 参数可选签名保护
- 前端收集所有组件状态，确保状态一致性

## 最佳实践

1. **事件命名规范**：使用 `模块:操作` 格式，如 `user:created`、`order:paid`
2. **组件命名**：使用 `named()` 方法为组件指定唯一 ID
3. **分片标记**：使用 `liveFragment()` 标记需要精准刷新的区域
4. **数据传递**：通过 `emit()` 传递必要的最小数据集
5. **状态同步**：确保所有监听者正确处理事件数据

## 注意事项

- HTTP 无状态：每次请求都需要传递所有组件的完整状态
- 组件实例化：监听组件会在每次事件触发时被实例化
- 性能考虑：避免在一个请求中更新过多组件
- 事件循环：避免 A → B → A 的事件循环

## 版本历史

- **v1.0.0** (2026-04-26): 初始版本，实现跨组件事件通信
