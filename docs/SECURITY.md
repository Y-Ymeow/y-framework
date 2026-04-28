# 安全规范

## 前端安全

### 禁止的内联事件处理器

**危险** - 以下内联事件处理器已被禁止：
- `onclick`
- `onload`
- `onerror`
- `onmouseover`
- `onmouseout`
- `onchange`
- `onsubmit`
- `onfocus`
- `onblur`
- 以及所有 `on*` 属性

**原因**：内联事件处理器容易导致 XSS 攻击，允许执行任意 JavaScript 代码。

**替代方案**：使用 `data-action` 服务端 action 或安全的 `data-on:*` 指令。

### data-on:* 表达式安全限制

`data-on:*` 指令的表达式执行受到严格限制，以下模式被禁止：

**注意**：表达式使用 `with()` 语句简化状态访问，因此不使用严格模式。安全性通过模式验证实现。

- `eval` - 动态代码执行
- `Function` - 构造函数
- `window.location =` - 页面重定向
- `window.open` - 打开新窗口
- `document.write` / `document.writeln` - 动态写入
- `document.cookie =` - 修改 cookie
- `document.domain =` - 修改域名
- `document.location =` - 页面重定向
- `localStorage.*` (除了 `setItem|getItem|removeItem|clear`)
- `sessionStorage.*` (除了 `setItem|getItem|removeItem|clear`)
- `fetch` / `XMLHttpRequest` - 网络请求
- `WebSocket` - WebSocket 连接
- `Worker` - Web Workers
- `import` / `require` - 模块导入
- `__proto__` / `constructor` / `prototype` - 原型链操作
- `...` - 扩展运算符
- `<script>` - script 标签
- `javascript:` - javascript 协议
- `on*=` - 内联事件赋值

**允许的操作**：
- 状态修改：`count++`, `showModal = true`
- 方法调用：`save()`, `handleClick()`
- 事件分发：`$dispatch('event-name', data)`
- 条件判断：`if (condition) { ... }`
- 本地存储：`localStorage.setItem('key', 'value')`, `localStorage.getItem('key')`
- DOM 样式：`document.body.style.paddingBottom = '10px'`
- DOM 类操作：`$el.classList.add('active')`, `$el.classList.remove('active')`

### Script 标签过滤

所有 HTML 内容（包括 `data-html`、`data-bind-html`、组件片段）都会自动过滤 `<script>` 标签，防止注入恶意脚本。

**后端过滤**：`Element::sanitizeHtml()`
**前端过滤**：`sanitizeHtml()` 函数

### 安全的事件注册机制

使用 `registerEventHandler()` 注册安全的事件处理器：

```javascript
import { registerEventHandler } from './y-ui/directives.js';

// 注册安全的事件处理器
registerEventHandler('my-custom-action', (event, context) => {
    // 安全的代码
    console.log('Event:', event);
});
```

## 后端安全

### Document::main() 限制

`Document::main()` 方法**只接受 Element、LiveComponent 或 UXComponent 对象**，禁止直接传入 HTML 字符串：

```php
// ❌ 危险 - 禁止直接传入 HTML 字符串
Document::make()->main('<div>content</div>');

// ✅ 安全 - 使用 Element 对象
Document::make()->main(
    Container::make()->children(
        Text::p('content')
    )
);

// ✅ 安全 - 使用 LiveComponent
Document::make()->main($liveComponent);

// ✅ 安全 - 使用 UXComponent
Document::make()->main($uxComponent);
```

**原因**：直接传入 HTML 字符串可能包含恶意 script 标签，绕过安全过滤。

### Response::html() 类型安全

`Response::html()` 方法接受 `mixed` 类型，但通过 `Document::main()` 的类型检查来确保安全：

```php
// ✅ 安全 - 自动通过 Document::main() 验证
Response::html($element);
Response::html($liveComponent);
Response::html($uxComponent);

// ❌ 错误 - 会抛出 InvalidArgumentException
Response::html('<div>content</div>');
```

### Document::inject() 过滤

所有通过 `Document::inject()` 注入的 HTML 都会自动过滤 `<script>` 标签：

```php
// 自动过滤 script 标签
Document::injectStatic('head', '<script>alert(1)</script>');
Document::make()->injectHead('<script>alert(1)</script>');
// 输出: (空)
```

### 禁止的 HTML 属性

在 PHP 代码中，禁止使用 `attr()` 方法设置 `on*` 属性：

```php
// ❌ 危险
$element->attr('onclick', 'alert(1)');

// ✅ 安全
$element->liveAction('methodName');
$element->liveParams(['key' => 'value']);
```

### HTML 内容过滤

所有通过 `html()` 方法设置的 HTML 内容都会自动过滤 `<script>` 标签：

```php
// 自动过滤 script 标签
$element->html('<div><script>alert(1)</script></div>');
// 输出: <div></div>
```

### Script 标签完全禁止

`Element` 类会完全禁止创建 `<script>` 标签：

```php
// ❌ 危险 - script 标签内容会被清空
Element::make('script')->text('alert(1)');
// 输出: <script></script>
```

### 服务端 Action

使用 `liveAction()` 替代内联事件：

```php
$element
    ->liveAction('saveData')
    ->liveParams(['id' => $id]);
```

## 最佳实践

### 1. 使用服务端 Action

对于需要与后端交互的操作，使用 `data-action`：

```php
Element::make('button')
    ->liveAction('deleteUser')
    ->liveParams(['id' => $userId])
    ->text('删除');
```

### 2. 使用 data-on:* 进行纯前端逻辑

对于纯前端状态管理，使用 `data-on:*`：

```php
Element::make('button')
    ->bindOn('click', 'count++')
    ->text('增加');
```

### 3. 一次性注册事件处理器

在应用初始化时注册所有需要的事件处理器：

```javascript
// resources/js/ux.js
import { registerEventHandler } from './y-ui/directives.js';

registerEventHandler('openModal', (event, context) => {
    // 安全的模态框打开逻辑
});

registerEventHandler('closeModal', (event, context) => {
    // 安全的模态框关闭逻辑
});
```

### 4. 避免动态执行代码

不要使用 `eval`、`new Function()` 或动态生成的事件处理器。

### 5. 输入验证

所有用户输入都应该在后端进行验证和清理。

## 安全检查清单

- [ ] 没有使用 `onclick` 等 `on*` 属性
- [ ] 没有在 `data-on:*` 表达式中使用危险模式
- [ ] 没有在 HTML 内容中包含 `<script>` 标签
- [ ] 使用 `liveAction()` 替代内联事件
- [ ] 使用 `registerEventHandler()` 注册事件处理器
- [ ] 所有用户输入都经过验证
- [ ] `Document::main()` 只传入 Element、LiveComponent 或 UXComponent 对象
- [ ] 不直接创建 `<script>` 标签
- [ ] `Response::html()` 只传入 Element、LiveComponent 或 UXComponent 对象

## 违规检测

系统会在控制台输出安全警告：

```
Security: Forbidden expression pattern detected: eval(...)
Security: Forbidden expression pattern detected: window.location
```

如果看到这些警告，请立即修复相关代码。
