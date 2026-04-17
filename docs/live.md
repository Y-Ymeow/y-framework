# Y-Live: 精准片段更新

Y-Live 是本框架的一项创新技术，允许开发者在不离开 PHP 环境的情况下，实现高效的局部 DOM 更新。它的工作方式类似于 Phoenix LiveView 或 Laravel Livewire，但更为轻量。

## 工作原理

1. **注册与识别**：使用 `LiveComponent(id, renderer)` 包裹需要动态更新的部分。
2. **前端监听**：`y-ui.js` 自动监听带有 `data-live-action` 的交互。
3. **精准请求**：当交互触发时，前端发送包含 `X-Live-Id` 的请求。
4. **局部渲染**：后端识别到 `X-Live-Id` 后，仅执行该组件的渲染函数并返回。
5. **高效合并**：前端使用 `Idiomorph` 库将返回的 HTML 片段与现有 DOM 进行形态合并（Morphing），保留输入框焦点和滚动位置。

## 示例：计数器

### 后端代码 (`app/Actions/Counter.php`)

```php
use function Framework\UI\{LiveComponent, div, button};

function Counter() {
    return LiveComponent('main-counter', function($props) {
        $count = $props['count'] ?? 0;
        
        return div(['class' => 'p-4 border'],
            div([], "当前计数值: {$count}"),
            button([
                'data-live-action' => 'increment',
                'class' => 'btn'
            ], "增加")
        );
    });
}
```

### 前端初始化 (`resources/js/app.js`)

```javascript
import { Y } from './y-ui.js';
Y.boot();
```

## 核心助手函数

### `LiveComponent(string $id, callable $renderer, array $props = [])`

- `$id`: 唯一的组件标识符。
- `$renderer`: 渲染函数，接收 `$props`。
- `$props`: 初始状态。

## 优势

- **减少数据传输**：仅传输改变的 HTML 片段，而不是整个页面或笨重的 JSON。
- **状态保留**：由于使用 Morphing 技术，页面其他部分的 JavaScript 状态（如播放器、展开的菜单）不会被重置。
- **零 JS 逻辑**：开发者无需编写任何 AJAX 处理代码或状态同步逻辑。
