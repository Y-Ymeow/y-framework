# UI 组件系统 (View System)

## 概述
框架提供了一套完整的声明式 UI 构建系统。所有组件均继承自 `Element` 基类，支持链式调用，并原生集成响应式指令（Directives）和 Live 系统。

## Element 基类
`Framework\View\Base\Element` 是所有视图组件的核心。

### 基础 API
```php
$el = Element::make('div')
    ->id('my-node')                // 设置 ID
    ->class('bg-white p-4')        // 追加 Class
    ->style('color: red')          // 设置内联样式
    ->attr('title', '提示')        // 设置任意属性
    ->data('key', 'value')         // 设置 data-* 属性
    ->children('内容', $otherEl);   // 组合子元素
```

### 响应式指令 (Directives)
这些指令由前端 `y-ui` 引擎解析，实现零延迟交互。

- `bindText(expr)`: 绑定文本内容（`data-text`）。
- `bindHtml(expr)`: 绑定 HTML 内容（`data-html`）。
- `bindModel(key)`: 双向绑定表单状态（`data-model`）。
- `bindShow(expr)`: 切换显示隐藏（`data-show`）。
- `bindIf(expr)`: 切换 DOM 存在（`data-if`）。
- `bindOn(event, expr)`: 绑定事件监听（`data-on:click`）。
- `dataClass(expr)`: 动态绑定 Class（`data-class`）。

### Live 集成 API
- `liveAction(action, event)`: 绑定服务端 Action 触发（`data-action`）。
- `liveFragment(name)`: **核心方法**。标记该元素为一个“分片”，可被服务端精准刷新。
- `liveBind(key)`: 快捷绑定 state 到 textContent。

## 常用内置组件

### 1. Container (容器)
用于布局和结构。支持快捷语义化方法：
```php
Container::main();    // <main>
Container::section(); // <section>
Container::nav();     // <nav>
```

### 2. Text (文本)
提供语义化文本标签：
```php
Text::h1('大标题');
Text::p('段落文字');
Text::strong('加粗');
Text::small('备注');
```

### 3. Row & Grid (布局)
基于 CSS Flex 和 Grid 的高级封装：
```php
Row::make()->gap(4)->children(...);
Grid::make()->cols(3)->gap(4)->children(...);
```

## Document 页面包装 (新架构)

框架现在支持**全自动页面包装**。

### 自动包装机制
当你在路由中通过 `Response::html()` 返回内容时，如果内容不含 `<html>` 标签，框架会自动使用 `Document` 类包裹你的内容，并自动注入：
- CSRF Token Meta
- 核心资源 (`AssetRegistry::core()`)
- CSS 引擎动态样式 (`/_css`)

### 静态助手方法
你可以通过 `Document` 的静态方法配置页面级属性：
```php
Document::setTitle('页面标题');
Document::setLang('zh-CN');
Document::uxStatic(); // 一键开启 UX 资源加载

// 全局代码注入 (如统计脚本)
Document::injectStatic('head', '<script>...</script>');
Document::injectStatic('body_start', '<div>...</div>'); // 等同于 injectBeforeBody
Document::injectStatic('body_end', '<footer>...</footer>'); // 等同于 injectAfterBody
```

### 实例注入 (页面级)
在特定的视图中，你也可以针对当前 `Document` 实例进行注入：
```php
$doc = Document::make('标题');

$doc->injectHead('<link rel="canonical" href="...">');
$doc->injectBodyStart('<div id="overlay"></div>');
$doc->injectBodyEnd('<script src="analytics.js"></script>');

// 或者使用通用的 inject 方法
$doc->inject('head', '<!-- custom comment -->');
```

### 手动加载资产
```php
use Framework\View\Document\AssetRegistry;

AssetRegistry::getInstance()
    ->css('/my.css')
    ->js(vite('resources/js/ux.js')); // 推荐使用 vite 助手
```

## 渲染机制
- 调用 `render()` 返回 HTML 字符串。
- `Element` 实现了 `__toString()`，可以直接在字符串中使用。
- **自动收集**: 任何标记了 `liveFragment()` 的元素，在 `render()` 执行时会自动将其 HTML 备份到 `FragmentRegistry` 中，供 Live 系统按需截取。

## 最佳实践：组合 UI
```php
public function render(): Element
{
    return Container::main()->class('p-8')->children(
        Text::h1('我的应用'),
        Row::make()->children(
            Button::make()->label('确定')->primary(),
            Button::make()->label('取消')->secondary()
        )->fragment('action-bar') // 标记为分片
    );
}
```
