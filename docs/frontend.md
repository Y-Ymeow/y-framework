# 前端框架 (Y-UI)

## 概述

轻量级响应式前端框架（~20KB / 7KB gzip），基于标准 HTML5 `data-*` 属性。

核心特性：
- 响应式状态管理（Signal）
- `data-*` 属性声明绑定，JS 通过 `el.dataset` 解析
- `$dispatch` + `data-on:*` 事件系统
- Live Component 服务器通信（State Patches）
- idiomorph DOM morphing + View Transitions API

## 初始化

框架在 `DOMContentLoaded` 时自动 `Y.boot()`，扫描所有 `[data-state]` 和 `[data-live]` 元素。

## data-state

声明响应式状态，所有子元素共享同一 scope。

```html
<div data-state='{"count": 0, "open": true, "name": ""}'>
  <!-- 子元素可以访问 count, open, name -->
</div>
```

值可以是 JSON 或 JS 表达式：
```html
<div data-state='{ count: 0, items: [] }'>
```

嵌套 scope：子 `[data-state]` 创建独立 scope，可通过 `$root` 访问父级。

## 绑定属性

### data-text

绑定 textContent：

```html
<span data-text="count"></span>
<span data-text="name || '匿名'"></span>
```

### data-html

绑定 innerHTML（注意 XSS 风险）：

```html
<div data-html="richText"></div>
```

### data-show

控制 display：

```html
<div data-show="open">面板内容</div>
<div data-show="count > 0">有数据</div>
```

配合 `data-transition` 可在 show/hide 时执行 enter/leave 动画：

```html
<div
  data-show="open"
  data-transition="{
    enter: {
      duration: 220,
      easing: 'ease-out',
      from: { opacity: '0', transform: 'translateY(8px)' },
      to: { opacity: '1', transform: 'translateY(0)' }
    },
    leave: {
      duration: 180,
      easing: 'ease-in',
      from: { opacity: '1', transform: 'translateY(0)' },
      to: { opacity: '0', transform: 'translateY(8px)' }
    }
  }"
>
  面板内容
</div>
```

### data-if

条件渲染（添加/移除 DOM）：

```html
<div data-if="loaded">加载完成</div>
```

### data-model

双向绑定：

```html
<input data-model="name" type="text">
<textarea data-model="content"></textarea>
<select data-model="selected">...</select>
<input data-model="agree" type="checkbox">
```

修饰符：
- `data-model="key.lazy"` — change 事件而非 input
- `data-model="key.number"` — 自动转 Number
- `data-model="key.boolean"` — 自动转 Boolean

### data-for

列表渲染，**必须绑定到 `<template>` 标签**：

```html
<template data-for="item in items">
  <div data-text="item"></div>
</template>

<template data-for="(item, index) in items">
  <span data-text="index"></span>: <span data-text="item"></span>
</template>
```

模板中可用 `{{ item }}` / `{{ index }}` 插值。

### data-bind / data-bind-* / data-bind:

**核心概念**：单向绑定，将 `data-state` 中的变量值同步到 DOM 元素。
`data-bind` 只负责 **State → DOM** 的更新（与 `data-model` 双向绑定不同）。

#### 1. 简写：`data-bind="key"`
绑定 state 到元素的**文本内容**（`textContent`），等价于 `data-text="key"`。

```html
<div data-state='{"count": 0, "name": "张三"}'>
  <span data-bind="count">0</span>          <!-- 显示 0 -->
  <span data-bind="name"></span>            <!-- 显示 张三 -->
</div>
```

#### 2. 属性绑定：`data-bind-属性名="key"`
绑定 state 到元素的**任意 HTML 属性**：

```html
<div data-state='{"url": "/home", "imageUrl": "/logo.png", "loading": true}'>
  <input data-bind-value="name">                            <!-- value -->
  <a data-bind-href="url">链接</a>                          <!-- href -->
  <img data-bind-src="imageUrl">                            <!-- src -->
  <button data-bind-disabled="loading">提交</button>        <!-- disabled -->
  <input data-bind-checked="agree" type="checkbox">         <!-- checked -->
  <div data-bind-title="tooltip">悬停提示</div>             <!-- 任意属性 -->
</div>
```

支持的内置属性：`value`, `html`, `href`, `src`, `disabled`, `checked`。
其他属性（如 `title`, `alt`, `data-*` 等）也会自动解析。

#### 3. 对象语法：`data-bind:属性名="对象"`
通过**对象**批量设置属性，常用于动态 class 或 style：

```html
<div data-state='{"isActive": true, "textColor": "red"}'>
  <!-- 对象语法：isActive 为 true 时添加 active 类 -->
  <div data-bind:class="{'active': isActive, 'hidden': !isActive}">内容</div>
  
  <!-- 批量设置多个 style -->
  <div data-bind:style="{'color': textColor, 'font-size': '16px'}">内容</div>
</div>
```

> 对象语法中，值为 `true` 时设置属性，值为 `false` 时移除属性。

### data-class

动态 class（支持对象语法）：
```html
<!-- 对象语法：{'类名': 条件} -->
<div data-class="{'bg-white': isActive, 'bg-gray-200': !isActive, 'font-bold': isImportant}">
    内容
</div>

<!-- 数组语法 -->
<div data-class="[classA, classB, isActive && 'active-class']">内容</div>

<!-- 字符串语法 -->
<div data-class="dynamicClass">内容</div>
```

> 静态 class 会保留，动态 class 会叠加到静态 class 上。

### data-ref

注册元素引用到 `$refs`：

```html
<input data-ref="searchInput" data-model="query">
<button data-on:click="$refs.searchInput.focus()">聚焦</button>
```

### data-effect

副作用执行：

```html
<div data-effect="console.log('count changed:', count)"></div>
```

### data-cloak

隐藏未初始化元素（框架启动后自动移除）：

```html
<div data-cloak>...</div>
```

默认样式为：

```css
[data-cloak] { display: none !important; }
```

PHP Builder 可直接写：

```php
Container::make()
    ->cloak()
    ->bindShow('open');
```

## 事件系统

### data-on:*

统一事件绑定，`data-on:{事件名}` 格式：

**原生事件：**
```html
<button data-on:click="count++">+1</button>
<form data-on:submit.prevent="save()">保存</form>
<input data-on:input="search()">
<div data-on:keydown.enter="submit()"></div>
```

**修饰符（用 `.` 分隔）：**
- `.prevent` — preventDefault
- `.stop` — stopPropagation
- `.self` — 仅自身触发
- `.once` — 只触发一次
- `.outside` — 点击外部触发
- `.enter` / `.escape` / `.ctrl` 等按键修饰符

**自定义事件（window 级别）：**
```html
<!-- 监听自定义事件 -->
<div data-on:modal:open="showModal = true"
     data-on:modal:close="showModal = false">

<div data-on:toast:show="message = $event.message; type = $event.type"
     data-on:toast:hide="message = ''">
```

### $dispatch

触发自定义事件，直接 `window.dispatchEvent`：

```javascript
$dispatch(eventName)                    // 无数据
$dispatch(eventName, detail)            // 带数据
```

```html
<button data-on:click="$dispatch('modal:open')">打开 Modal</button>
<button data-on:click="$dispatch('modal:close')">关闭 Modal</button>
<button data-on:click="$dispatch('toast:show', { message: '成功！', type: 'success' })">Toast</button>
```

### 完整事件流程

```
触发端                              监听端
──────────────────────────────────────────────────────
$dispatch('modal:open')    →    window    →    data-on:modal:open="show = true"
$dispatch('toast:show', d) →    window    →    data-on:toast:show="msg = $event.message"
服务器 dispatch operation   →    window    →    data-on:*
```

## 魔法变量

所有 `data-*` 表达式中可用：

| 变量 | 说明 | 示例 |
|------|------|------|
| `$dispatch(name, detail)` | 触发自定义事件 | `data-on:click="$dispatch('modal:open')"` |
| `$watch(key, callback)` | 监听 state 变化 | `data-effect="$watch('count', (val, old) => console.log(val))"` |
| `$root` | 根 `data-state` 的 state proxy | `data-text="$root.title"` |
| `$el` | 当前执行指令的 DOM 元素 | `data-on:click="$el.classList.toggle('active')"` |
| `$nextTick(callback)` | DOM 更新后执行 | `data-on:click="items.push('x'); $nextTick(() => scroll())"` |
| `$refs` | `data-ref` 元素集合 | `data-on:click="$refs.input.focus()"` |
| `$event` | 事件对象（data-on: 表达式中） | `data-on:click="handle($event.target.value)"` |
| `$locale(locale?)` | 切换语言 / 获取当前语言 | `data-on:click="$locale('zh')"` |

## Live Component

### data-live

标记服务器端 Live 组件：

### data-live-fragment

在 Live 组件内部声明一个可更新分片。服务端返回 fragment 更新时，只允许命中当前组件内部已声明的分片名：

```html
<div data-live="App\\Components\\TodoList">
  <div data-live-fragment="list">...</div>
  <div data-live-fragment="stats">...</div>
</div>
```

服务端：

```php
return LiveResponse::make()
    ->fragment('list', $this->renderList())
    ->fragment('stats', $this->renderStats())
    ->dispatch('list:updated', null, ['count' => $this->total]);
```

也支持追加模式：

```php
return LiveResponse::make()->fragment('list', $newItemsHtml, 'append');
```

### 安全说明

- `/live/update` 现在要求有效的 CSRF token。页面会自动输出 `<meta name="csrf-token">`，前端请求会自动携带 `X-CSRF-Token`。
- `LiveResponse::html()`、`LiveResponse::domPatch()`、`LiveResponse::appendHtml()` 在写入 DOM 前会经过净化。`script` / `iframe` / 内联 `on*` 事件会被移除，绝大多数 `data-*` 指令也会被剥掉。
- 更推荐使用 `LiveResponse::fragment()` 更新组件内部已声明的 `data-live-fragment`，而不是任意 selector。这样更新范围更小，也更容易控制安全边界。
- Live HTML 更新默认只保留 `data-action`、`data-action-event`、`data-action-params` 这类服务端 action 触发所需属性；`data-state`、`data-on:*`、`data-html`、`data-effect`、`data-show` 等会被移除，避免通过服务端返回 HTML 激活新的前端表达式。
- `data-action-params` 不应再手写裸 JSON。框架现在要求它使用签名封装格式；未签名参数会被前端拒绝，并在服务端再次验签。
- `LiveResponse::js()` 已禁用。需要客户端行为时，改用 `dispatch()` 下发事件，再由前端预先注册好的监听逻辑处理。

```html
<div data-live="App\Components\FlagGenerator"
     data-live-id="flag-gen-1"
     data-live-state="base64..."
     data-state='{"flagCode":"","count":0}'>
  <span data-bind="count">0</span>
  <span data-bind="flagCode"></span>
  <button data-action="generate">生成</button>
  <button data-action="reset">重置</button>
</div>
```

### data-action

标记触发服务器 action 的元素：

```html
<button data-action="generate">生成</button>
<button data-action="reset" data-action-event="click">重置</button>
```

点击时自动 `POST /live/update`，携带组件类名、action、state。

### 服务器响应

```json
{
  "success": true,
  "state": "base64...",
  "patches": { "flagCode": "FLAG-ABC", "count": 1 },
  "operations": [...]
}
```

`patches` 合并到响应式 state → `data-bind`/`data-text` 自动更新 DOM，**不返回 HTML**。

### 服务器 Operations

| op | 参数 | 说明 |
|----|------|------|
| `openModal` | id | 打开 modal |
| `closeModal` | id | 关闭 modal |
| `redirect` | url | 跳转（支持 View Transition） |
| `reload` | | 刷新页面 |
| `js` | code | 执行 JS |
| `dispatch` | event, detail | 触发自定义事件（推荐，用 toast/notify 时内部也是转这个） |
| `update` | target, value | 更新表单值 |
| `html` | selector, html | 替换 innerHTML |
| `domPatch` | selector, html | idiomorph morph（推荐用于更新 HTML 片段） |
| `remove` | selector | 移除元素 |
| `addClass` | selector, class | 添加 class |
| `removeClass` | selector, class | 移除 class |

## 国际化 (Intl)

框架内置 i18n 支持，通过 `data-intl` 属性标记需翻译元素，配合后端 `Translator` 实现语言切换。

### data-intl

标记元素需要翻译，值为翻译 key：

```html
<span data-intl="messages.welcome"></span>
<p data-intl="messages.description"></p>
```

渲染时服务端会自动替换为对应语言的翻译文本。

### 切换语言

使用 `$locale()` 函数（通过指令系统调用，安全无 XSS）：

```html
<button data-on:click="$locale('zh')">中文</button>
<button data-on:click="$locale('en')">English</button>

<!-- 在表达式中使用当前语言 -->
<span data-text="$locale === 'zh' ? '你好' : 'Hello'"></span>
```

### 工作原理

1. 页面加载时，前端收集所有 `data-intl` 元素的 key
2. 切换语言时，`POST /live/intl` 批量获取翻译
3. 前端自动替换所有 `data-intl` 元素的文本内容
4. `document.documentElement.lang` 自动更新

### PHP 中使用

```php
use Framework\Intl\Translator;

// 初始化（bootstrap 中）
Translator::init(__DIR__ . '/../resources/lang', 'zh', 'en');

// 组件中使用
Element::make('h1')->intl('messages.welcome');
```

## Modal / Drawer / Toast 模式

不需要专门组件，用 `data-state` + `data-show` + `$dispatch` + `data-on:*`：

```html
<div data-state='{"showModal": false}'>
  <button data-on:click="$dispatch('my-modal:open')">打开</button>

  <div data-on:my-modal:open="showModal = true"
       data-on:my-modal:close="showModal = false">
    <div data-show="showModal" class="modal-overlay">
      <div class="modal-content">
        <p>Modal 内容</p>
        <button data-on:click="$dispatch('my-modal:close')">关闭</button>
      </div>
    </div>
  </div>
</div>
```

## API

### Y.boot(root?)

初始化框架，扫描 `root` 下所有 `[data-state]` 和 `[data-live]` 元素。

### Y.register(name, definition)

注册客户端组件（高级用法）。

### Y.executeOperation(op)

手动执行一个 operation 对象。

## 脚本收集器与按需加载 (Asset Registry)

为了安全和性能，框架不再鼓励使用内联 `<script>` 标签。取而代之的是一套命名的脚本收集系统。

### 1. 注册脚本

在 PHP 中全局或按需注册命名的脚本块：

```php
Document::registerScript('chart-lib', "console.log('Chart library initialized');");
```

### 2. 声明依赖

任何 `Element` 都可以声明它依赖某些脚本。框架会自动去重并合并请求：

```php
// 只有当这个元素被渲染时，对应的 JS 才会通过 /_js 路由加载
Element::make('div')
    ->requireScript('chart-lib')
    ->html('<canvas id="myChart"></canvas>');
```

### 3. 快捷方式

在 `Document` 实例中直接注册并使用：

```php
$doc->script('custom-logic', "alert('Hello!');");
```

### 4. 工作原理

- 脚本内容通过 `cache()` 存储在服务端。
- 渲染时，框架通过 `<script src="/_js?ids=id1,id2&v=hash" defer></script>` 统一加载。
- 浏览器会缓存这些合并后的脚本，且支持 `immutable` 缓存策略。

