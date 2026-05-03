# View 视图系统 — API 参考

> 由 DocGen 自动生成于 2026-05-03 15:49:15

## 目录

**其他**
- [`AssetRegistry`](#framework-view-document-assetregistry)
- [`Container`](#framework-view-container) — Container — 布局容器
- [`Document`](#framework-view-document-document) — HTML 文档构建器
- [`Element`](#framework-view-base-element) — Element — HTML 元素基类
- [`Form`](#framework-view-form) — Form — 表单及表单元素
- [`Fragment`](#framework-view-fragment)
- [`FragmentRegistry`](#framework-view-fragmentregistry)
- [`Image`](#framework-view-image) — Image — 图片
- [`Link`](#framework-view-link) — Link — 链接
- [`Listing`](#framework-view-listing) — Listing — 列表
- [`LiveResponse`](#framework-view-liveresponse)
- [`Table`](#framework-view-table) — Table — 表格
- [`Text`](#framework-view-text) — Text — 文本元素

---

### 其他

<a name="framework-view-document-assetregistry"></a>
#### `Framework\View\Document\AssetRegistry`

**文件:** `php/src/View/Document/AssetRegistry.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `getInstance` |  | — |
| `reset` |  | — |
| `registerScript` | 注册命名的 JS 代码块（持久化到缓存） | `string $id`, `string $js` |
| `requireScript` | 请求加载特定的脚本 ID | `string $id` |
| `getScriptContent` |  | `string $id` |
| `css` |  | `string $href`, `?string $id` = null |
| `js` |  | `string $src`, `bool $defer` = true, `?string $id` = null, `bool $isModule` = false |
| `inlineStyle` |  | `string $style` |
| `core` | 核心资源注册 | — |
| `ui` |  | — |
| `ux` |  | — |
| `renderCss` |  | — |
| `renderJs` |  | — |
| `getCssList` | 获取已注册的 CSS 文件列表（用于 WASM JSON 输出） | — |
| `getJsList` | 获取已注册的 JS 文件列表（用于 WASM JSON 输出） | — |


<a name="framework-view-container"></a>
#### `Framework\View\Container`

Container — 布局容器

**继承:** `Framework\View\Base\Element`  | **实现:** `Stringable`  | **文件:** `php/src/View/Container.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `section` |  | — |
| `nav` |  | — |
| `article` |  | — |
| `aside` |  | — |
| `main` |  | — |
| `header` |  | — |
| `footer` |  | — |
| `flex` |  | `string $direction` = 'row' |
| `grid` |  | `int $cols` = 1 |
| `gap` |  | `int $size` = 4 |
| `p` |  | `int $size` = 4 |
| `mx` |  | `string $size` = 'auto' |
| `spaceY` |  | `int $size` = 4 |
| `spaceX` |  | `int $size` = 4 |
| `itemsCenter` |  | — |
| `justifyBetween` |  | — |
| `justifyEnd` |  | — |
| `wFull` |  | — |
| `rounded` |  | `string $size` = 'lg' |
| `shadow` |  | `string $size` = 'md' |
| `bg` |  | `string $color` |
| `overflow` |  | `string $type` = 'hidden' |
| `minH` |  | `string $size` = 'screen' |


<a name="framework-view-document-document"></a>
#### `Framework\View\Document\Document`

HTML 文档构建器

**实现:** `Stringable`  | **文件:** `php/src/View/Document/Document.php`

**常量：**

| 常量 | 值 | 说明 |
|---|---|---|
| `MODE_FULL` | `'full'` | 渲染模式 |
| `MODE_PARTIAL` | `'partial'` |  |
| `MODE_FRAGMENT` | `'fragment'` |  |

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `make` |  | `string $title` = '' |
| `mode` | 设置渲染模式 | `string $mode` |
| `registerScript` | 全局注册 JS | `string $id`, `string $js` |
| `injectStatic` |  | `string $location`, `string $html` |
| `addMeta` |  | `string $name`, `string $content` |
| `setTitle` |  | `string $title` |
| `setLang` |  | `string $lang` |
| `inject` |  | `string $location`, `string $html` |
| `sseConfig` | 启用 SSE 实时推送功能 | `array $channels` = [] |
| `requireScript` | 标记加载脚本 | `string $ids` |
| `script` | 注册并加载脚本 | `string $id`, `string $js` |
| `meta` | 实例级别添加 Meta | `string $name`, `string $content` |
| `title` |  | `string $title` |
| `lang` |  | `string $lang` |
| `ux` |  | — |
| `css` |  | `string $href`, `?string $id` = null |
| `js` |  | `string $src`, `bool $defer` = true, `?string $id` = null |
| `header` |  | `mixed $content` |
| `main` |  | `mixed $content` |
| `footer` |  | `mixed $content` |
| `render` |  | — |
| `toJson` | 输出 JSON 格式（用于 Tauri JS Bridge 调用） | — |


<a name="framework-view-base-element"></a>
#### `Framework\View\Base\Element`

Element — HTML 元素基类

**实现:** `Stringable`  | **文件:** `php/src/View/Base/Element.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `make` | 静态工厂方法 — 创建 Element 实例 | `Framework\View\Base\Element\|string\|null $tagOrcontent` = null, `Framework\View\Base\Element\|string\|null $content` = null |
| `id` | 设置元素 ID | `string $id` |
| `class` | 添加 CSS 类名（支持多个，自动合并） | `string $classes` |
| `attr` | 设置单个 HTML 属性 | `string $name`, `string $value` |
| `attrs` | 批量设置 HTML 属性（合并到已有属性） | `array $attrs` |
| `data` | 设置 data-* 自定义属性 | `string $key`, `string $value` |
| `dataLiveSse` | 订阅 SSE 频道（data-live-sse） | `string $channels` |
| `style` | 设置内联样式 | `string $style` |
| `text` | 设置纯文本内容（自动 HTML 转义，防 XSS） | `?string $text` = null |
| `html` | 设置 HTML 内容（经过安全过滤） | `mixed $html` = null |
| `child` | 添加单个子元素 | `mixed $child` |
| `children` | 批量添加子元素 | `mixed $children` |
| `state` | 设置 data-state 状态数据（JSON 编码） | `array $data` |
| `visible` | 快捷设置元素可见性 | `bool $visible` = true |
| `hidden` | 快捷设置元素隐藏 | — |
| `model` | 声明式模型绑定（data-model） | `string $name` |
| `liveModel` | LiveComponent 双向绑定（增强版） | `string $name` |
| `bindText` | 文本绑定指令（data-text） | `string $expr` |
| `bindHtml` | HTML 绑定指令（data-html） | `string $expr` |
| `bindModel` | 模型绑定指令（data-model）— 别名方法 | `string $key` |
| `bindShow` | 显示/隐藏绑定（data-show） | `string $expr` |
| `bindTransition` | 过渡动画绑定（data-transition） | `string $expr` |
| `bindIf` | 条件渲染绑定（data-if） | `string $expr` |
| `bindFor` | 循环渲染绑定（data-for） | `string $expr` |
| `bindOn` | 事件绑定指令（data-on:event） | `string $event`, `string $expr` |
| `bindAttr` | 属性绑定指令（data-bind:attr） | `string $attr`, `array\|string $expr` |
| `dataClass` | CSS 类绑定（data-bind:class） | `string $expr` |
| `bindEffect` | 副作用绑定（data-effect） | `string $expr` |
| `bindRef` | 引用绑定（data-ref） | `string $name` |
| `intl` | 国际化翻译（data-intl） | `string $key` |
| `cloak` | 隐藏未渲染内容（data-cloak） | — |
| `liveFragment` | 声明 LiveComponent 分片更新区域（data-live-fragment） | `string $name` |
| `liveBind` | LiveComponent 数据绑定（data-bind / data-bind-type） | `string $key`, `string $type` = 'text' |
| `liveAction` | LiveComponent Action 绑定（data-action） | `string $action`, `string $event` = 'click' |
| `liveParams` | LiveComponent Action 参数（data-action-params） | `array\|string $params` |
| `requireScript` | 声明依赖的 JS 脚本 | `string $ids` |
| `render` | 渲染元素为 HTML 字符串 | — |


<a name="framework-view-form"></a>
#### `Framework\View\Form`

Form — 表单及表单元素

**继承:** `Framework\View\Base\Element`  | **实现:** `Stringable`  | **文件:** `php/src/View/Form.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `action` |  | `string $url` |
| `method` |  | `string $method` |
| `multipart` |  | — |
| `input` |  | `string $name` = '' |
| `password` |  | `string $name` = '' |
| `email` |  | `string $name` = '' |
| `number` |  | `string $name` = '' |
| `hiddenField` |  | `string $name` = '' |
| `textarea` |  | `string $name` = '' |
| `select` |  | `string $name` = '' |
| `checkbox` |  | `string $name` = '' |
| `button` |  | `string $label` = '' |
| `submit` |  | `string $label` = '提交' |
| `label` |  | `string $text` = '' |


<a name="framework-view-fragment"></a>
#### `Framework\View\Fragment`

**继承:** `Framework\View\Base\Element`  | **实现:** `Stringable`  | **文件:** `php/src/View/Fragment.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `make` | 这里的第一个参数必须兼容基类的签名，我们将其视为 Fragment 的名称 | `mixed $nameOrTag` = null, `mixed $tag` = 'span' |


<a name="framework-view-fragmentregistry"></a>
#### `Framework\View\FragmentRegistry`

**文件:** `php/src/View/FragmentRegistry.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `setTargets` | 设置本次请求需要“抓取”的分片名称及其模式 (replace, append, prepend) | `array $targets` |
| `record` | 收集分片 HTML | `string $name`, `Framework\View\Base\Element $element` |
| `getFragments` |  | — |
| `reset` |  | — |


<a name="framework-view-image"></a>
#### `Framework\View\Image`

Image — 图片

**继承:** `Framework\View\Base\Element`  | **实现:** `Stringable`  | **文件:** `php/src/View/Image.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `src` |  | `string $src` |
| `alt` |  | `string $alt` |
| `width` |  | `string\|int $w` |
| `height` |  | `string\|int $h` |
| `objectCover` |  | — |
| `objectContain` |  | — |
| `rounded` |  | `string $size` = 'lg' |


<a name="framework-view-link"></a>
#### `Framework\View\Link`

Link — 链接

**继承:** `Framework\View\Base\Element`  | **实现:** `Stringable`  | **文件:** `php/src/View/Link.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `href` |  | `string $href` |
| `target` |  | `string $target` |
| `blank` |  | — |
| `download` |  | `?string $filename` = null |


<a name="framework-view-listing"></a>
#### `Framework\View\Listing`

Listing — 列表

**继承:** `Framework\View\Base\Element`  | **实现:** `Stringable`  | **文件:** `php/src/View/Listing.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `ul` |  | — |
| `ol` |  | — |
| `dl` |  | — |
| `dd` |  | — |
| `dt` |  | — |
| `li` |  | — |
| `items` |  | `array $items` |
| `pairs` |  | `array $pairs` |
| `render` |  | — |
| `listDisc` |  | — |
| `listDecimal` |  | — |
| `listNone` |  | — |


<a name="framework-view-liveresponse"></a>
#### `Framework\View\LiveResponse`

**文件:** `php/src/View/LiveResponse.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `make` |  | — |
| `update` |  | `string $field`, `mixed $value` |
| `html` |  | `string $selector`, `string $html` |
| `domPatch` |  | `string $selector`, `string $html` |
| `append` |  | `string $selector`, `string $html` |
| `appendHtml` |  | `string $selector`, `string $html` |
| `remove` |  | `string $selector` |
| `addClass` |  | `string $selector`, `string $class` |
| `removeClass` |  | `string $selector`, `string $class` |
| `toast` |  | `string $message`, `string $type` = 'success', `int $duration` = 3000 |
| `notify` |  | `string $title`, `string $message`, `string $type` = 'info', `int $duration` = 5000 |
| `openModal` |  | `string $id` |
| `closeModal` |  | `string $id` |
| `redirect` |  | `string $url` |
| `reload` |  | — |
| `js` |  | `string $code` |
| `dispatch` |  | `string $event`, `?string $target` = null, `mixed $detail` = null |
| `fragment` |  | `string $name`, `string $html`, `string $mode` = 'replace' |
| `fragments` |  | `array $fragments` |
| `toArray` |  | — |


<a name="framework-view-table"></a>
#### `Framework\View\Table`

Table — 表格

**继承:** `Framework\View\Base\Element`  | **实现:** `Stringable`  | **文件:** `php/src/View/Table.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `headers` |  | `array $headers` |
| `rows` |  | `array $rows` |
| `row` |  | `array $row` |
| `striped` |  | `bool $v` = true |
| `hoverable` |  | `bool $v` = true |
| `bordered` |  | `bool $v` = true |
| `compact` |  | `bool $v` = true |
| `emptyText` |  | `string $text` |
| `render` |  | — |


<a name="framework-view-text"></a>
#### `Framework\View\Text`

Text — 文本元素

**继承:** `Framework\View\Base\Element`  | **实现:** `Stringable`  | **文件:** `php/src/View/Text.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `h1` |  | `Framework\View\Base\Element\|string\|null $content` = null |
| `h2` |  | `Framework\View\Base\Element\|string\|null $content` = null |
| `h3` |  | `Framework\View\Base\Element\|string\|null $content` = null |
| `h4` |  | `Framework\View\Base\Element\|string\|null $content` = null |
| `h5` |  | `Framework\View\Base\Element\|string\|null $content` = null |
| `h6` |  | `Framework\View\Base\Element\|string\|null $content` = null |
| `p` |  | `Framework\View\Base\Element\|string\|null $content` = null |
| `strong` |  | `Framework\View\Base\Element\|string\|null $content` = null |
| `em` |  | `Framework\View\Base\Element\|string\|null $content` = null |
| `small` |  | `Framework\View\Base\Element\|string\|null $content` = null |
| `blockquote` |  | `Framework\View\Base\Element\|string\|null $content` = null |
| `code` |  | `Framework\View\Base\Element\|string\|null $content` = null |
| `pre` |  | `Framework\View\Base\Element\|string\|null $content` = null |
| `fontBold` |  | — |
| `fontSemibold` |  | — |
| `textSm` |  | — |
| `textXs` |  | — |
| `textLg` |  | — |
| `textXl` |  | — |
| `text2xl` |  | — |
| `text3xl` |  | — |
| `textGray` |  | `string $shade` = '500' |
| `textWhite` |  | — |
| `textCenter` |  | — |
| `textRight` |  | — |
| `truncate` |  | — |
| `uppercase` |  | — |
| `leading` |  | `string $size` = 'normal' |


