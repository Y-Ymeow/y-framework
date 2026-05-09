# 模板系统（Element）文档

> 框架不写 HTML，所有 UI 通过 Element 链式调用构建。本文档覆盖 Element 核心、语义元素、Live 指令、响应式指令、Tailwind 快捷方法、安全机制。

---

## 目录

1. [设计理念](#1-设计理念)
2. [Element 核心 API](#2-element-核心-api)
3. [语义元素快捷类](#3-语义元素快捷类)
4. [LiveComponent 集成指令](#4-livecomponent-集成指令)
5. [响应式指令系统（y-directive）](#5-响应式指令系统y-directive)
6. [辅助功能](#6-辅助功能)
7. [Tailwind CSS 快捷方法](#7-tailwind-css-快捷方法)
8. [安全机制](#8-安全机制)
9. [Document 与资源管理](#9-document-与资源管理)
10. [Fragment 分片系统](#10-fragment-分片系统)
11. [完整方法速查](#11-完整方法速查)

---

## 1. 设计理念

### 为什么不写 HTML？

传统 PHP 模板（Blade/Twig）混合 HTML 和逻辑，容易产生：
- XSS 漏洞（忘记转义）
- 不一致的组件结构
- 难以复用的 UI 片段

Element 系统的核心思想：**用 PHP 对象构建 DOM 树，自动处理安全、国际化、交互绑定**。

### 能力分层

```
第一层：基础 HTML 构建
  make(), id(), class(), attr(), text(), child(), children()
  ↓
第二层：LiveComponent 集成
  liveModel(), liveAction(), liveBind(), liveFragment()
  ↓
第三层：响应式指令（y-directive 前端引擎）
  bindText(), bindShow(), bindFor(), bindOn(), bindAttr(), ...
  ↓
第四层：辅助功能
  intl(), cloak(), requireScript(), state()
```

### 类继承关系

```
Element (基类)
  ├── HasLiveDirectives      Live 指令
  ├── HasBindDirectives      响应式指令
  └── HasVisibility          可见性控制

Container extends Element
  ├── HasTailwindSpacing     间距快捷
  ├── HasTailwindLayout      布局快捷
  └── HasTailwindAppearance  外观快捷

Text extends Element
  └── HasTailwindTypography  排版快捷

Image extends Element
  └── HasTailwindAppearance

Form extends Element
Link extends Element
Table extends Element
Listing extends Element
Fragment extends Element
```

---

## 2. Element 核心 API

### 2.1 创建元素

```php
// 基础创建
$div = Element::make('div');
$input = Element::make('input');
$span = Element::make('span');
```

### 2.2 设置属性

```php
// ID
$el->id('main-container');

// CSS 类（追加，不覆盖）
$el->class('p-4', 'bg-white', 'rounded');

// 单个属性
$el->attr('href', '/home');
$el->attr('type', 'text');
$el->attr('placeholder', '请输入');

// 批量属性
$el->attrs([
    'href' => '/page',
    'target' => '_blank',
    'rel' => 'noopener',
]);

// data-* 属性
$el->data('id', '123');
$el->data('user', 'john');

// 内联样式
$el->style('color: red; font-size: 14px');
```

### 2.3 设置内容

```php
// 纯文本（自动 HTML 转义）
$el->text('Hello World');
$el->text('<script>alert("xss")</script>');  // 安全转义

// HTML 内容（经过安全过滤）
$el->html('<strong>Bold</strong>');           // 允许
$el->html('<script>alert(1)</script>');       // script 被移除
```

### 2.4 添加子元素

```php
// 单个子元素
$el->child(Element::make('span')->text('Hello'));

// 多个子元素
$el->children(
    Element::make('li')->text('Item 1'),
    Element::make('li')->text('Item 2'),
    Element::make('li')->text('Item 3')
);

// 混合类型
$el->child(Element::make('div')->text('Element'));
$el->child($liveComponent);    // LiveComponent 自动 toHtml()
$el->child($uxComponent);      // UXComponent 自动 render()
$el->child('plain string');    // 字符串自动安全过滤
```

### 2.5 渲染输出

```php
// 方式一：render()
$html = $el->render();

// 方式二：__toString()
echo $el;  // 自动调用 render()
```

### 2.6 链式调用

```php
// 所有方法返回 $this，支持链式调用
$html = Element::make('div')
    ->id('container')
    ->class('flex', 'items-center', 'gap-4')
    ->child(
        Element::make('h1')
            ->class('text-2xl', 'font-bold')
            ->text('标题')
    )
    ->child(
        Element::make('p')
            ->class('text-gray-600')
            ->text('描述文字')
    )
    ->render();
```

输出：
```html
<div id="container" class="flex items-center gap-4">
    <h1 class="text-2xl font-bold">标题</h1>
    <p class="text-gray-600">描述文字</p>
</div>
```

### 2.7 Void 元素

以下标签自动识别为 void 元素（自闭合）：

```
area, base, br, col, embed, hr, img, input, link, meta, param, source, track, wbr
```

```php
Element::make('input')->attr('type', 'text')->attr('name', 'email');
// <input type="text" name="email">
```

---

## 3. 语义元素快捷类

### 3.1 Container — 容器元素

```php
use Framework\View\Element\Container;

// 基础
$div = Container::make();                    // <div>
$section = Container::section();             // <section>
$nav = Container::nav();                     // <nav>
$article = Container::article();             // <article>
$aside = Container::aside();                 // <aside>
$main = Container::main();                   // <main>
$header = Container::header();               // <header>
$footer = Container::footer();               // <footer>
```

Container 继承 Tailwind 快捷方法：`p()`, `px()`, `py()`, `m()`, `mx()`, `flex()`, `grid()`, `rounded()`, `shadow()`, `bg()`, `border()` 等。

### 3.2 Text — 文本元素

```php
use Framework\View\Element\Text;

$h1 = Text::h1('大标题');
$h2 = Text::h2('二级标题');
$h3 = Text::h3('三级标题');
$p = Text::p('段落文字');
$strong = Text::strong('强调文字');
$em = Text::em('斜体文字');
$small = Text::small('小号文字');
$blockquote = Text::blockquote('引用');
$code = Text::code('代码');
$pre = Text::pre('预格式化');
```

Text 继承排版快捷方法：`fontBold()`, `fontSemibold()`, `textSm()`, `textLg()`, `textXl()`, `textCenter()` 等。

### 3.3 Link — 链接元素

```php
use Framework\View\Element\Link;

$link = Link::make('/home')
    ->text('首页')
    ->blank();                   // target="_blank"

$link->href('/about');
$link->download('file.pdf');
```

### 3.4 Image — 图片元素

```php
use Framework\View\Element\Image;

$img = Image::make('/photo.jpg')
    ->alt('照片')
    ->width(200)
    ->height(150)
    ->objectCover()
    ->rounded('lg');
```

### 3.5 Form — 表单元素

```php
use Framework\View\Element\Form;

$form = Form::make()
    ->action('/users')
    ->method('POST')
    ->multipart();              // enctype="multipart/form-data"

// 表单控件
$input = Form::input('name')->placeholder('姓名');
$email = Form::email('email');
$password = Form::password('password');
$number = Form::number('age');
$hidden = Form::hiddenField('token');
$textarea = Form::textarea('content')->rows(5);
$select = Form::select('role')->options(['admin' => '管理员', 'user' => '用户']);
$checkbox = Form::checkbox('agree');
$button = Form::button('点击');
$submit = Form::submit('提交');
$label = Form::label('用户名');
```

### 3.6 Table — 表格元素

```php
use Framework\View\Element\Table;

$table = Table::make()
    ->headers(['ID', '名称', '状态'])
    ->rows([
        [1, '项目A', '活跃'],
        [2, '项目B', '暂停'],
    ])
    ->striped()
    ->hoverable()
    ->bordered()
    ->compact()
    ->emptyText('暂无数据');
```

### 3.7 Listing — 列表元素

```php
use Framework\View\Element\Listing;

$ul = Listing::ul()
    ->items(['苹果', '香蕉', '橙子'])
    ->listDisc();

$ol = Listing::ol()
    ->items(['第一步', '第二步', '第三步'])
    ->listDecimal();

$dl = Listing::dl()
    ->pairs([
        '名称' => '项目A',
        '状态' => '活跃',
    ]);
```

---

## 4. LiveComponent 集成指令

> 这些方法在 LiveComponent 的 `render()` 中使用，实现前后端双向交互。

### 4.1 liveModel() — 双向数据绑定

```php
// 将输入框与 LiveComponent 的 #[State] 属性双向绑定
Element::make('input')
    ->liveModel('username')
    ->attr('type', 'text');

// 同时设置 data-live-model 和 data-model
// 用户输入自动同步到 $this->username
```

### 4.2 liveAction() — Action 绑定

```php
// 基础：点击触发
Element::make('button')
    ->liveAction('save')
    ->text('保存');
// → data-action:click="save()"

// 指定事件
Element::make('input')
    ->liveAction('search', 'input')
    ->attr('type', 'text');
// → data-action:input="search()"

// 带参数
Element::make('button')
    ->liveAction('delete', 'click', ['id' => 42])
    ->text('删除');
// → data-action:click="delete(id: 42)"

// 参数值类型自动转换
Element::make('button')
    ->liveAction('toggle', 'click', [
        'active' => true,       // → true
        'count' => 5,           // → 5
        'name' => 'test',       // → 'test'
        'data' => ['a' => 1],   // → {"a":1}
    ]);
```

### 4.3 liveBind() — 数据绑定

```php
// 文本绑定
Element::make('span')->liveBind('username', 'text');
// → data-bind="username"

// 值绑定
Element::make('input')->liveBind('email', 'value');
// → data-bind-value="email"

// 选中绑定
Element::make('input')->liveBind('agree', 'checked');
// → data-bind-checked="agree"
```

### 4.4 liveFragment() — 分片更新

```php
// 标记分片区域
Element::make('div')
    ->liveFragment('user-info')
    ->child(Element::make('span')->text($this->username));

// 在 LiveAction 中返回分片更新
// LiveResponse::make()->fragment('user-info', $html);
```

### 4.5 liveDisabled() — 禁用条件

```php
Element::make('button')
    ->liveAction('submit')
    ->liveDisabled('count === 0')
    ->text('提交');
// 当 count === 0 时，Action 不会触发
```

### 4.6 dataLiveSse() — SSE 订阅

```php
Element::make('div')
    ->id('dashboard')
    ->dataLiveSse('dashboard', 'notifications');
// → data-live-sse="dashboard,notifications"
```

---

## 5. 响应式指令系统（y-directive）

> 这些属性由前端 y-directive 引擎解析，实现类似 Vue 的声明式绑定。

### 5.1 指令对照表

| 方法 | data-* 属性 | 功能 | 类比 Vue |
|------|-------------|------|----------|
| `bindText($expr)` | `data-text` | 动态文本 | `{{ expr }}` |
| `bindHtml($expr)` | `data-html` | 动态 HTML | `v-html` |
| `bindModel($key)` | `data-model` | 表单双向绑定 | `v-model` |
| `bindShow($expr)` | `data-show` | 显示/隐藏 | `v-show` |
| `bindIf($expr)` | `data-if` | 条件渲染 | `v-if` |
| `bindFor($expr)` | `data-for` | 列表循环 | `v-for` |
| `bindOn($event, $expr)` | `data-on:{event}` | 事件监听 | `v-on` |
| `bindAttr($attr, $expr)` | `data-bind:{attr}` | 动态属性 | `v-bind` |
| `dataClass($expr)` | `data-bind:class` | 动态 CSS 类 | `:class` |
| `bindEffect($expr)` | `data-effect` | 副作用 | `watch` |
| `bindRef($name)` | `data-ref` | DOM 引用 | `ref` |
| `bindTransition($expr)` | `data-transition` | 过渡动画 | `transition` |

### 5.2 使用示例

```php
// 动态文本
Element::make('span')->bindText('user.name');

// 条件显示
Element::make('div')->bindShow('isVisible');

// 条件渲染（不渲染到 DOM）
Element::make('div')->bindIf('isLoggedIn');

// 列表循环
Element::make('ul')
    ->bindFor('item in items')
    ->child(
        Element::make('li')
            ->bindText('item.name')
            ->dataClass("{ active: item.id === selectedId }")
    );

// 事件监听
Element::make('button')
    ->bindOn('click', 'handleClick')
    ->text('点击');

// 动态属性
Element::make('a')
    ->bindAttr('href', 'url')
    ->bindText('linkText');

// 动态 CSS 类
Element::make('div')
    ->dataClass("{ 'bg-green-500': isActive, 'bg-red-500': !isActive }");

// 副作用
Element::make('div')
    ->bindEffect('fetchData(searchTerm)');

// DOM 引用
Element::make('input')
    ->bindRef('searchInput')
    ->attr('type', 'text');

// 过渡动画
Element::make('div')
    ->bindTransition('fade')
    ->bindShow('isVisible');
```

---

## 6. 辅助功能

### 6.1 intl() — 国际化

```php
// 基础
Element::make('span')->intl('welcome.message');
// 自动从语言包加载翻译

// 带参数
Element::make('span')->intl('user.greeting', ['name' => 'John']);

// 带默认文本
Element::make('span')->intl('admin.dashboard', [], '控制台');
// 翻译键不存在时显示 "控制台"

// 属性级国际化
Element::make('input')->intlAttr('placeholder', 'search.placeholder', [], '搜索...');
// → data-intl="search.placeholder" placeholder="搜索..."
```

### 6.2 cloak() — 防闪烁

```php
Element::make('div')
    ->cloak()
    ->bindText('dynamicContent');
// JS 加载前隐藏，防止显示原始模板
// → data-cloak
```

### 6.3 requireScript() — 依赖声明

```php
Element::make('div')
    ->requireScript('ux:chart', 'ux:datepicker');
// 确保 AssetRegistry 加载指定脚本
```

### 6.4 comment() — 注释节点

```php
// 输出 HTML 注释而非实际标签
// 用于只注册 JS/CSS 但不输出可见 DOM 的场景
Element::make('div')
    ->comment('toast placeholder')
    ->requireScript('ux:toast');
// 输出: <!-- toast placeholder -->
```

### 6.5 state() — 状态存储

```php
Element::make('div')
    ->state(['userId' => 42, 'role' => 'admin']);
// → data-state='{"userId":42,"role":"admin"}'
```

### 6.6 visible() / hidden()

```php
$el->visible(true);    // data-visible="true"
$el->visible(false);   // hidden + data-visible="false"
$el->hidden();         // hidden + data-visible="false"
```

---

## 7. Tailwind CSS 快捷方法

### 7.1 间距（HasTailwindSpacing）

Container 元素可用：

```php
$el->p(4);          // p-4
$el->px(6);         // px-6
$el->py(2);         // py-2
$el->pt(4);         // pt-4
$el->pb(4);         // pb-4
$el->pl(4);         // pl-4
$el->pr(4);         // pr-4
$el->m(4);          // m-4
$el->mx(6);         // mx-6
$el->my(2);         // my-2
$el->mt(4);         // mt-4
$el->mb(4);         // mb-4
$el->ml(4);         // ml-4
$el->mr(4);         // mr-4
$el->gap(4);        // gap-4
```

### 7.2 布局（HasTailwindLayout）

Container 元素可用：

```php
$el->flex();                    // flex
$el->flex('col');               // flex flex-col
$el->grid(3);                   // grid grid-cols-3
$el->itemsCenter();             // items-center
$el->itemsStart();              // items-start
$el->itemsEnd();                // items-end
$el->justifyCenter();           // justify-center
$el->justifyBetween();          // justify-between
$el->justifyStart();            // justify-start
$el->justifyEnd();              // justify-end
$el->w('full');                 // w-full
$el->w('1/2');                  // w-1/2
$el->w('auto');                 // w-auto
$el->h('screen');               // h-screen
$el->h('full');                 // h-full
$el->minH('screen');            // min-h-screen
$el->maxW('4xl');               // max-w-4xl
$el->maxW('7xl');               // max-w-7xl
$el->overflow('hidden');        // overflow-hidden
$el->overflow('auto');          // overflow-auto
$el->relative();                // relative
$el->absolute();                // absolute
$el->fixed();                   // fixed
$el->z(50);                     // z-50
$el->inset(0);                  // inset-0
```

### 7.3 外观（HasTailwindAppearance）

Container / Image 元素可用：

```php
$el->rounded('lg');             // rounded-lg
$el->rounded('full');           // rounded-full
$el->rounded('none');           // rounded-none
$el->shadow('md');              // shadow-md
$el->shadow('lg');              // shadow-lg
$el->shadow('none');            // shadow-none
$el->bg('white');               // bg-white
$el->bg('blue-500');            // bg-blue-500
$el->bg('gray-100');            // bg-gray-100
$el->border('gray-200');        // border border-gray-200
$el->border('red-300');         // border border-red-300
$el->opacity(50);               // opacity-50
```

### 7.4 排版（HasTailwindTypography）

Text 元素可用：

```php
$el->fontBold();                // font-bold
$el->fontSemibold();            // font-semibold
$el->fontNormal();              // font-normal
$el->textSm();                  // text-sm
$el->textLg();                  // text-lg
$el->textXl();                  // text-xl
$el->text2xl();                 // text-2xl
$el->textCenter();              // text-center
$el->textLeft();                // text-left
$el->textRight();               // text-right
$el->textColor('gray-600');     // text-gray-600
$el->textColor('blue-500');     // text-blue-500
$el->leading('tight');          // leading-tight
$el->tracking('wide');          // tracking-wide
$el->uppercase();               // uppercase
$el->lowercase();               // lowercase
$el->truncate();                // truncate
```

---

## 8. 安全机制

### 8.1 XSS 防护层级

| 层级 | 机制 | 说明 |
|------|------|------|
| 1 | `text()` 自动转义 | `htmlspecialchars($text, ENT_QUOTES, 'UTF-8')` |
| 2 | `html()` 白名单过滤 | 移除 script/style/iframe 等危险标签 |
| 3 | `attr()` 过滤 on* 事件 | 自动拒绝 onclick/onerror 等属性 |
| 4 | `attr()` 过滤协议 | 自动拒绝 javascript:/data: 协议值 |
| 5 | `script` 标签清空 | script 标签内容强制为空 |

### 8.2 text() — 安全转义

```php
Element::make('div')->text('<script>alert("xss")</script>');
// 输出: <div>&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;</div>
```

### 8.3 html() — 白名单过滤

```php
// 允许的安全标签
$allowed = ['a', 'abbr', 'b', 'blockquote', 'br', 'cite', 'code', 'dd', 'del',
    'details', 'dfn', 'div', 'dl', 'dt', 'em', 'figcaption', 'figure',
    'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'hr', 'i', 'img', 'ins', 'kbd',
    'li', 'mark', 'ol', 'p', 'pre', 'q', 's', 'samp', 'small', 'span',
    'strong', 'sub', 'summary', 'sup', 'table', 'tbody', 'td', 'tfoot',
    'th', 'thead', 'time', 'tr', 'u', 'ul', 'var'];

// 危险标签自动移除
$dangerous = ['script', 'style', 'iframe', 'object', 'embed', 'applet', 'meta', 'link', 'base'];
```

### 8.4 属性过滤

```php
// on* 事件属性自动拒绝
$el->attr('onclick', 'alert(1)');  // 无效，不设置

// 危险协议自动拒绝
$el->attr('href', 'javascript:alert(1)');  // 无效
$el->attr('src', 'data:text/html,...');     // 无效
```

---

## 9. Document 与资源管理

### 9.1 Document 类

```php
use Framework\View\Document\Document;

$doc = Document::make('页面标题');
$doc->mode(Document::MODE_FULL);       // 完整 HTML 文档
$doc->mode(Document::MODE_PARTIAL);    // 部分 HTML（WASM 模式）
$doc->mode(Document::MODE_FRAGMENT);   // 片段
```

### 9.2 AssetRegistry

```php
// 注册脚本
Document::registerScript('ux:chart', '/assets/chart.js');

// 声明依赖
$el->requireScript('ux:chart');

// 静态注入
Document::injectStatic('head', '<meta name="custom" content="value">');
Document::injectStatic('body_end', '<script>...</script>');

// Meta 标签
Document::addMeta('description', '网站描述');
Document::setTitle('页面标题');
Document::setLang('zh-CN');
```

### 9.3 SSE 配置

```php
Document::sseConfig(['dashboard', 'notifications']);
// 在 <head> 中注入 SSE meta 元素
```

---

## 10. Fragment 分片系统

### 10.1 Fragment 类

```php
use Framework\View\Fragment;

$fragment = Fragment::make('user-info');
// → <span data-live-fragment="user-info" style="display: contents">
```

### 10.2 FragmentRegistry

```php
// liveFragment() 自动注册到 FragmentRegistry
$el->liveFragment('user-info');
// FragmentRegistry::getInstance()->record('user-info', $el);
```

### 10.3 分片更新流程

```
1. render() 中标记分片：
   $el->liveFragment('user-info');

2. LiveAction 中返回分片更新：
   LiveResponse::make()->fragment('user-info', $newHtml);

3. 前端 y-live 引擎只替换该分片的 DOM
```

---

## 11. 完整方法速查

### Element 基础方法

| 方法 | 说明 |
|------|------|
| `make($tag)` | 创建元素 |
| `id($id)` | 设置 ID |
| `class(...$classes)` | 添加 CSS 类 |
| `attr($name, $value)` | 设置属性 |
| `attrs($attrs)` | 批量设置属性 |
| `getAttr($key)` | 获取属性 |
| `data($key, $value)` | 设置 data-* 属性 |
| `style($style)` | 设置内联样式 |
| `text($text)` | 设置文本（安全转义） |
| `html($html)` | 设置 HTML（安全过滤） |
| `child($child)` | 添加子元素 |
| `children(...$children)` | 批量添加子元素 |
| `getChildren()` | 获取子元素列表 |
| `model($name)` | 设置 data-model |
| `intl($key, $params, $default)` | 国际化 |
| `intlAttr($attr, $key, $params, $default)` | 属性级国际化 |
| `requireScript(...$ids)` | 声明 JS 依赖 |
| `comment($text)` | 标记为注释节点 |
| `render()` | 渲染为 HTML |
| `visible($bool)` | 设置可见性 |
| `hidden()` | 隐藏 |
| `cloak()` | 防闪烁 |
| `state($data)` | 设置 data-state |

### Live 指令方法

| 方法 | 说明 |
|------|------|
| `liveModel($name)` | 双向数据绑定 |
| `liveAction($action, $event, $params)` | Action 绑定 |
| `liveBind($key, $type)` | 数据绑定 |
| `liveFragment($name)` | 分片标记 |
| `liveDisabled($expr)` | 禁用条件 |
| `dataLiveSse(...$channels)` | SSE 订阅 |

### 响应式指令方法

| 方法 | 说明 |
|------|------|
| `bindText($expr)` | 动态文本 |
| `bindHtml($expr)` | 动态 HTML |
| `bindModel($key)` | 表单绑定 |
| `bindShow($expr)` | 显示/隐藏 |
| `bindIf($expr)` | 条件渲染 |
| `bindFor($expr)` | 列表循环 |
| `bindOn($event, $expr)` | 事件监听 |
| `bindAttr($attr, $expr)` | 动态属性 |
| `dataClass($expr)` | 动态 CSS 类 |
| `bindEffect($expr)` | 副作用 |
| `bindRef($name)` | DOM 引用 |
| `bindTransition($expr)` | 过渡动画 |
