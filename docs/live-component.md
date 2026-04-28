# Live Component 系统

## 概述

Live Component 是框架的核心交互引擎。它允许你使用纯 PHP 编写交互式组件，通过 `POST /live/update` 请求与服务器通信。系统会自动处理状态同步、局部 DOM 更新（分片更新）和异步指令（Operations）。

新增国际化支持：通过 `POST /live/intl` 实现翻译切换，使用 `data-intl` 属性标记需翻译元素。

## 核心开发模式：分片更新 (Fragment Update)

这是框架推荐的局部刷新方式。它比全量 Patch 更轻量，比 `data-text` 指令更具表现力（支持复杂 HTML 逻辑）。

### 1. 标记分片
在 `render()` 方法中，对需要局部更新的元素调用 `->liveFragment('name')`。

### 2. 触发刷新
在 `#[LiveAction]` 方法中，调用 `$this->refresh('name')`。

### 3. 示例代码

```php
class Counter extends LiveComponent
{
    public int $count = 0;

    #[LiveAction]
    public function increment(): void
    {
        $this->count++;
        // 标记 counter-box 在响应时自动重新渲染并返回
        $this->refresh('counter-box');
        $this->toast("当前数值: {$this->count}");
    }

    public function render(): Element
    {
        return Container::make()->children(
            Text::p()->children(
                '计数器: ',
                // 标记此 Element 为分片，名称为 counter-box
                Text::strong((string)$this->count)->liveFragment('counter-box')
            ),
            Button::make()->label('增加')->primary()->liveAction('increment')
        );
    }
}
```

## 指令系统 (Operations)

`LiveComponent` 提供了一系列方法，用于从服务端直接控制前端行为。

### 常用内置指令

| 方法 | 说明 |
|------|------|
| `$this->toast(message, type, duration)` | 显示全局 Toast 提示 |
| `$this->openModal(id)` | 打开指定的 UX Modal |
| `$this->closeModal(id)` | 关闭指定的 UX Modal |
| `$this->selectTab(tabsId, tabId)` | 切换指定的 UX Tabs |
| `$this->toggleAccordion(id, open)` | 切换 UX 手风琴折叠状态 |
| `$this->refresh(names...)` | 触发分片自动收集并更新 |
| `$this->redirect(url)` | 页面跳转 |
| `$this->refreshPage()` | 刷新当前页面 |

### 通用 UX 指令接口

你可以使用 `$this->ux()` 方法调用任何兼容的 UX 组件动作：
```php
$this->ux('drawer', 'main-drawer', 'open');
```

## 响应式指令 (Directives)

除了服务端驱动的分片更新，你还可以使用前端指令实现零延迟响应：

- `data-text="count"`: 自动同步 state.count 到 textContent。
- `data-model="name"`: 双向绑定表单值。
- `data-show="isVisible"`: 控制显示隐藏。
- `data-on:click="count++"`: 纯前端逻辑处理。
- `data-effect="$expr"`: 响应式副作用，当表达式中的状态变化时自动执行。

## data-effect 动态效果

`data-effect` 用于实现响应式的副作用，例如动态更新 DOM、调用浏览器 API、持久化状态到 localStorage 等。

### 基本语法

```php
$el->attr('data-effect', "$.count > 10 ? document.body.classList.add('high-count') : document.body.classList.remove('high-count')");
```

- `$.property` 访问组件的公共属性
- 表达式在属性的响应式上下文中执行，当依赖的状态变化时自动重新执行
- 可以访问所有全局 JavaScript API（`document`、`localStorage` 等）

### Admin 侧边栏示例

Admin 布局使用 `data-effect` 实现侧边栏的动态折叠，无需等待 AJAX 响应：

```php
// AdminLayout.php
protected function renderSidebar(): Element
{
    $sidebar = Element::make('aside')
        ->class('admin-sidebar')
        ->liveFragment('admin-sidebar');
    
    // 当 sidebarCollapsed 变化时，立即更新 body class 和 localStorage
    $sidebar->attr('data-effect', 
        "$.sidebarCollapsed ? document.body.classList.add('admin-sidebar-collapsed') 
                            : document.body.classList.remove('admin-sidebar-collapsed');
         localStorage.setItem('admin_sidebar_collapsed', $.sidebarCollapsed ? '1' : '0')"
    );
    
    return $sidebar;
}

#[LiveAction]
public function toggleSidebar(): void
{
    $this->sidebarCollapsed = !$this->sidebarCollapsed;
    $this->refresh('admin-sidebar');
}
```

### 工作原理

1. 组件属性 `sidebarCollapsed` 被序列化到 `data-state`
2. JavaScript 的 `bindEffect()` 监听表达式中的状态依赖
3. 当 `$.sidebarCollapsed` 变化时，立即执行 `data-effect` 中的代码
4. CSS transition 提供平滑的动画效果
5. localStorage 持久化状态，刷新页面后自动恢复

### 使用场景

- **UI 状态持久化**: 侧边栏折叠、主题切换、面板展开/收起
- **即时反馈**: 计数更新、进度条、动态样式
- **浏览器集成**: 调用 `localStorage`、`sessionStorage`、`navigator` API

## 状态管理与序列化

### 属性可见性
- **Public 属性**: 自动同步到前端 `state`，并在请求间保持持久化（序列化）。
- **Protected/Private 属性**: 仅限后端使用，不参与同步。

### 序列化白名单
`$operations` 属性已被排除在序列化之外，确保指令（如 Toast）不会在多次请求间累加或重复触发。

## 页面自动包装 (Automatic Document Wrapping)

框架通过 `Response::html()` 实现了自动化的页面构建：

1. **自动识别**: 如果返回内容不含 `<html>`，系统自动调用 `Document` 包装。
2. **核心资产**: 包装时自动注入 `AssetRegistry::core()`（包含 CSS 引擎、y-ui 核心库）。
3. **元数据配置**:
   ```php
   Document::setTitle('我的页面');
   Document::uxStatic(); // 一键开启 UX 组件支持
   ```

## 国际化 (Intl)

Live Component 支持多语言切换，通过 `data-intl` 属性标记需要翻译的元素。

### 1. 后端配置

初始化翻译器，设置语言文件和默认语言：

```php
// bootstrap 或 ServiceProvider 中
Translator::init(__DIR__ . '/../resources/lang', 'zh', 'en');
```

语言文件格式（`resources/lang/zh/messages.php`）：
```php
<?php
return [
    'welcome' => '欢迎',
    'hello' => '你好 :name',
];
```

### 2. 在组件中使用

**方式一：使用 `intl()` 方法**
```php
class Welcome extends LiveComponent
{
    public function render(): Element
    {
        return Element::make('div')->children(
            Element::make('h1')->intl('messages.welcome'),
            Element::make('p')->intl('messages.hello')->text('Hello')
        );
    }
}
```

**方式二：直接在模板中写 `data-intl`**
```html
<span data-intl="messages.welcome"></span>
```

渲染时，服务端会自动将 `data-intl` 的值替换为对应翻译。

### 3. 前端切换语言

框架提供 `$locale` 全局函数用于切换语言：

```html
<!-- 使用指令系统（推荐，安全） -->
<button data-on:click="$locale('zh')">中文</button>
<button data-on:click="$locale('en')">English</button>

<!-- 在表达式中使用当前语言 -->
<span data-text="$locale === 'zh' ? '你好' : 'Hello'"></span>
```

切换语言时，前端会：
1. 收集页面所有 `data-intl` 的 key
2. `POST /live/intl` 请求批量翻译
3. 自动替换所有翻译内容

### 4. 路由

| 路由 | 说明 |
|------|------|
| `POST /live/update` | Live Component 主更新接口 |
| `POST /live/navigate` | 无刷新页面导航 |
| `POST /live/intl` | 语言切换，接收 `keys` 和 `locale` 参数 |

## 性能优化与安全

### 智能状态收集 (Performance)

为了应对大规模组件场景（如包含 1000 个列表项的页面），框架采用了 **“按需快照”** 策略：

1. **祖先链收集**：每次请求仅收集当前组件及其所有 **祖先组件** 的状态。
2. **监听器过滤**：只有明确通过 `#[LiveListener]` 声明了监听器的组件，其状态才会被收集（通过 `data-live-listeners` 属性识别）。
3. **效果**：极大减少了网络传输体积，防止在复杂页面下出现性能瓶颈。

### 安全加固机制 (Security)

1. **状态校验和 (Checksum)**：序列化的 `state` 字符串中包含公开属性的 MD5 指纹。如果攻击者在浏览器控制台篡改了 `data-state` 或 `_data` 请求包，后端校验将失败。
2. **会话绑定签名**：签名密钥由 `App Key` 与当前用户的 `Session Token` 共同哈希生成。这意味着 A 用户的加密状态字符串无法在 B 用户的会话中使用，彻底杜绝了跨用户状态伪造。
3. **类型强转**：后端在恢复属性时会严格根据 PHP 类型声明进行强转（int, bool, string 等），防止注入恶意脚本字符串。

## 渲染输出详解

当调用 `toHtml()` 时，根元素会被自动注入以下属性：

- `data-live`: 组件完整的类名。
- `data-live-id`: 唯一的组件实例 ID。
- `data-live-state`: 加密并压缩后的 Base64 状态字符串。
- `data-state`: 初始 JSON 状态。

## 通信流程 (Resolver)

1. **发起**: 前端捕获 `data-action`，收集 `data-state` 发起请求到 `/live/update`。
2. **还原**: `LiveComponentResolver` 实例化类，并通过 `deserializeState` 还原 `public` 属性。
3. **执行**: 调用对应的 `#[LiveAction]` 方法。
4. **收集**: 
   - 比较 Action 执行前后的属性差异，生成 `patches`。
   - 如果有 `refresh()` 标记，执行 `render()` 并抓取对应的 HTML 片段。
   - 收集 `$this->operations` 指令队列。
5. **响应**: 返回包含 `patches`, `fragments`, `operations` 的 JSON。

**导航请求**: 前端发起 `POST /live/navigate` 实现无刷新页面切换。

**翻译请求**: 切换语言时，前端发起 `POST /live/intl` 批量获取翻译。
