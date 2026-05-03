# View 视图系统 — 开发文档

> 由 DocGen 自动生成于 2026-05-03 15:49:15

## 组件清单

| 名称 | 命名空间 | 文件 | 类型 |
|---|---|---|---|
| `AssetRegistry` | `Framework\View\Document` | `php/src/View/Document/AssetRegistry.php` | class |
| `Container` | `Framework\View` | `php/src/View/Container.php` | extends Framework\View\Base\Element |
| `Document` | `Framework\View\Document` | `php/src/View/Document/Document.php` | class |
| `Element` | `Framework\View\Base` | `php/src/View/Base/Element.php` | class |
| `Form` | `Framework\View` | `php/src/View/Form.php` | extends Framework\View\Base\Element |
| `Fragment` | `Framework\View` | `php/src/View/Fragment.php` | extends Framework\View\Base\Element |
| `FragmentRegistry` | `Framework\View` | `php/src/View/FragmentRegistry.php` | class |
| `Image` | `Framework\View` | `php/src/View/Image.php` | extends Framework\View\Base\Element |
| `Link` | `Framework\View` | `php/src/View/Link.php` | extends Framework\View\Base\Element |
| `Listing` | `Framework\View` | `php/src/View/Listing.php` | extends Framework\View\Base\Element |
| `LiveResponse` | `Framework\View` | `php/src/View/LiveResponse.php` | class |
| `Table` | `Framework\View` | `php/src/View/Table.php` | extends Framework\View\Base\Element |
| `Text` | `Framework\View` | `php/src/View/Text.php` | extends Framework\View\Base\Element |

---

## 详细实现

### `Framework\View\Document\AssetRegistry`

- **文件:** `php/src/View/Document/AssetRegistry.php`

**公开方法 (15)：**

- `getInstance(): Framework\View\Document\AssetRegistry`
- `reset(): void`
- `registerScript(string $id, string $js): Framework\View\Document\AssetRegistry` — 注册命名的 JS 代码块（持久化到缓存）
- `requireScript(string $id): Framework\View\Document\AssetRegistry` — 请求加载特定的脚本 ID
- `getScriptContent(string $id): ?string`
- `css(string $href, ?string $id = null): Framework\View\Document\AssetRegistry`
- `js(string $src, bool $defer = true, ?string $id = null, bool $isModule = false): Framework\View\Document\AssetRegistry`
- `inlineStyle(string $style): Framework\View\Document\AssetRegistry`
- `core(): Framework\View\Document\AssetRegistry` — 核心资源注册
- `ui(): Framework\View\Document\AssetRegistry`
- `ux(): Framework\View\Document\AssetRegistry`
- `renderCss(): string`
- `renderJs(): string`
- `getCssList(): array` — 获取已注册的 CSS 文件列表（用于 WASM JSON 输出）
- `getJsList(): array` — 获取已注册的 JS 文件列表（用于 WASM JSON 输出）

### `Framework\View\Container`

- **文件:** `php/src/View/Container.php`
- **继承:** `Framework\View\Base\Element`

**公开方法 (23)：**

- `section(): static`
- `nav(): static`
- `article(): static`
- `aside(): static`
- `main(): static`
- `header(): static`
- `footer(): static`
- `flex(string $direction = 'row'): static`
- `grid(int $cols = 1): static`
- `gap(int $size = 4): static`
- `p(int $size = 4): static`
- `mx(string $size = 'auto'): static`
- `spaceY(int $size = 4): static`
- `spaceX(int $size = 4): static`
- `itemsCenter(): static`
- `justifyBetween(): static`
- `justifyEnd(): static`
- `wFull(): static`
- `rounded(string $size = 'lg'): static`
- `shadow(string $size = 'md'): static`
- `bg(string $color): static`
- `overflow(string $type = 'hidden'): static`
- `minH(string $size = 'screen'): static`

### `Framework\View\Document\Document`

- **文件:** `php/src/View/Document/Document.php`

**公开方法 (22)：**

- `make(string $title = ''): static`
- `mode(string $mode): static` — 设置渲染模式
- `registerScript(string $id, string $js): void` — 全局注册 JS
- `injectStatic(string $location, string $html): void`
- `addMeta(string $name, string $content): void`
- `setTitle(string $title): void`
- `setLang(string $lang): void`
- `inject(string $location, string $html): static`
- `sseConfig(array $channels = []): void` — 启用 SSE 实时推送功能
- `requireScript(string $ids): static` — 标记加载脚本
- `script(string $id, string $js): static` — 注册并加载脚本
- `meta(string $name, string $content): static` — 实例级别添加 Meta
- `title(string $title): static`
- `lang(string $lang): static`
- `ux(): static`
- `css(string $href, ?string $id = null): static`
- `js(string $src, bool $defer = true, ?string $id = null): static`
- `header(mixed $content): static`
- `main(mixed $content): static`
- `footer(mixed $content): static`
- `render(): string`
- `toJson(): string` — 输出 JSON 格式（用于 Tauri JS Bridge 调用）

### `Framework\View\Base\Element`

- **文件:** `php/src/View/Base/Element.php`

**公开方法 (37)：**

- `make(Framework\View\Base\Element|string|null $tagOrcontent = null, Framework\View\Base\Element|string|null $content = null): static` — 静态工厂方法 — 创建 Element 实例
- `id(string $id): static` — 设置元素 ID
- `class(string $classes): static` — 添加 CSS 类名（支持多个，自动合并）
- `attr(string $name, string $value): static` — 设置单个 HTML 属性
- `attrs(array $attrs): static` — 批量设置 HTML 属性（合并到已有属性）
- `data(string $key, string $value): static` — 设置 data-* 自定义属性
- `dataLiveSse(string $channels): static` — 订阅 SSE 频道（data-live-sse）
- `style(string $style): static` — 设置内联样式
- `text(?string $text = null): static` — 设置纯文本内容（自动 HTML 转义，防 XSS）
- `html(mixed $html = null): static` — 设置 HTML 内容（经过安全过滤）
- `child(mixed $child): static` — 添加单个子元素
- `children(mixed $children): static` — 批量添加子元素
- `state(array $data): static` — 设置 data-state 状态数据（JSON 编码）
- `visible(bool $visible = true): static` — 快捷设置元素可见性
- `hidden(): static` — 快捷设置元素隐藏
- `model(string $name): static` — 声明式模型绑定（data-model）
- `liveModel(string $name): static` — LiveComponent 双向绑定（增强版）
- `bindText(string $expr): static` — 文本绑定指令（data-text）
- `bindHtml(string $expr): static` — HTML 绑定指令（data-html）
- `bindModel(string $key): static` — 模型绑定指令（data-model）— 别名方法
- `bindShow(string $expr): static` — 显示/隐藏绑定（data-show）
- `bindTransition(string $expr): static` — 过渡动画绑定（data-transition）
- `bindIf(string $expr): static` — 条件渲染绑定（data-if）
- `bindFor(string $expr): static` — 循环渲染绑定（data-for）
- `bindOn(string $event, string $expr): static` — 事件绑定指令（data-on:event）
- `bindAttr(string $attr, array|string $expr): static` — 属性绑定指令（data-bind:attr）
- `dataClass(string $expr): static` — CSS 类绑定（data-bind:class）
- `bindEffect(string $expr): static` — 副作用绑定（data-effect）
- `bindRef(string $name): static` — 引用绑定（data-ref）
- `intl(string $key): static` — 国际化翻译（data-intl）
- `cloak(): static` — 隐藏未渲染内容（data-cloak）
- `liveFragment(string $name): static` — 声明 LiveComponent 分片更新区域（data-live-fragment）
- `liveBind(string $key, string $type = 'text'): static` — LiveComponent 数据绑定（data-bind / data-bind-type）
- `liveAction(string $action, string $event = 'click'): static` — LiveComponent Action 绑定（data-action）
- `liveParams(array|string $params): static` — LiveComponent Action 参数（data-action-params）
- `requireScript(string $ids): static` — 声明依赖的 JS 脚本
- `render(): string` — 渲染元素为 HTML 字符串

### `Framework\View\Form`

- **文件:** `php/src/View/Form.php`
- **继承:** `Framework\View\Base\Element`

**公开方法 (14)：**

- `action(string $url): static`
- `method(string $method): static`
- `multipart(): static`
- `input(string $name = ''): Framework\View\FormInput`
- `password(string $name = ''): Framework\View\FormInput`
- `email(string $name = ''): Framework\View\FormInput`
- `number(string $name = ''): Framework\View\FormInput`
- `hiddenField(string $name = ''): Framework\View\FormInput`
- `textarea(string $name = ''): Framework\View\FormTextarea`
- `select(string $name = ''): Framework\View\FormSelect`
- `checkbox(string $name = ''): Framework\View\FormInput`
- `button(string $label = ''): Framework\View\FormButton`
- `submit(string $label = '提交'): Framework\View\FormButton`
- `label(string $text = ''): Framework\View\Base\Element`

### `Framework\View\Fragment`

- **文件:** `php/src/View/Fragment.php`
- **继承:** `Framework\View\Base\Element`

**公开方法 (1)：**

- `make(mixed $nameOrTag = null, mixed $tag = 'span'): static` — 这里的第一个参数必须兼容基类的签名，我们将其视为 Fragment 的名称

### `Framework\View\FragmentRegistry`

- **文件:** `php/src/View/FragmentRegistry.php`

**公开方法 (4)：**

- `setTargets(array $targets): void` — 设置本次请求需要“抓取”的分片名称及其模式 (replace, append, prepend)
- `record(string $name, Framework\View\Base\Element $element): void` — 收集分片 HTML
- `getFragments(): array`
- `reset(): void`

### `Framework\View\Image`

- **文件:** `php/src/View/Image.php`
- **继承:** `Framework\View\Base\Element`

**公开方法 (7)：**

- `src(string $src): static`
- `alt(string $alt): static`
- `width(string|int $w): static`
- `height(string|int $h): static`
- `objectCover(): static`
- `objectContain(): static`
- `rounded(string $size = 'lg'): static`

### `Framework\View\Link`

- **文件:** `php/src/View/Link.php`
- **继承:** `Framework\View\Base\Element`

**公开方法 (4)：**

- `href(string $href): static`
- `target(string $target): static`
- `blank(): static`
- `download(?string $filename = null): static`

### `Framework\View\Listing`

- **文件:** `php/src/View/Listing.php`
- **继承:** `Framework\View\Base\Element`

**公开方法 (12)：**

- `ul(): static`
- `ol(): static`
- `dl(): static`
- `dd(): static`
- `dt(): static`
- `li(): static`
- `items(array $items): static`
- `pairs(array $pairs): static`
- `render(): string`
- `listDisc(): static`
- `listDecimal(): static`
- `listNone(): static`

### `Framework\View\LiveResponse`

- **文件:** `php/src/View/LiveResponse.php`

**公开方法 (20)：**

- `make(): Framework\View\LiveResponse`
- `update(string $field, mixed $value): Framework\View\LiveResponse`
- `html(string $selector, string $html): Framework\View\LiveResponse`
- `domPatch(string $selector, string $html): Framework\View\LiveResponse`
- `append(string $selector, string $html): Framework\View\LiveResponse`
- `appendHtml(string $selector, string $html): Framework\View\LiveResponse`
- `remove(string $selector): Framework\View\LiveResponse`
- `addClass(string $selector, string $class): Framework\View\LiveResponse`
- `removeClass(string $selector, string $class): Framework\View\LiveResponse`
- `toast(string $message, string $type = 'success', int $duration = 3000): Framework\View\LiveResponse`
- `notify(string $title, string $message, string $type = 'info', int $duration = 5000): Framework\View\LiveResponse`
- `openModal(string $id): Framework\View\LiveResponse`
- `closeModal(string $id): Framework\View\LiveResponse`
- `redirect(string $url): Framework\View\LiveResponse`
- `reload(): Framework\View\LiveResponse`
- `js(string $code): Framework\View\LiveResponse`
- `dispatch(string $event, ?string $target = null, mixed $detail = null): Framework\View\LiveResponse`
- `fragment(string $name, string $html, string $mode = 'replace'): Framework\View\LiveResponse`
- `fragments(array $fragments): Framework\View\LiveResponse`
- `toArray(): array`

### `Framework\View\Table`

- **文件:** `php/src/View/Table.php`
- **继承:** `Framework\View\Base\Element`

**公开方法 (9)：**

- `headers(array $headers): static`
- `rows(array $rows): static`
- `row(array $row): static`
- `striped(bool $v = true): static`
- `hoverable(bool $v = true): static`
- `bordered(bool $v = true): static`
- `compact(bool $v = true): static`
- `emptyText(string $text): static`
- `render(): string`

### `Framework\View\Text`

- **文件:** `php/src/View/Text.php`
- **继承:** `Framework\View\Base\Element`

**公开方法 (28)：**

- `h1(Framework\View\Base\Element|string|null $content = null): static`
- `h2(Framework\View\Base\Element|string|null $content = null): static`
- `h3(Framework\View\Base\Element|string|null $content = null): static`
- `h4(Framework\View\Base\Element|string|null $content = null): static`
- `h5(Framework\View\Base\Element|string|null $content = null): static`
- `h6(Framework\View\Base\Element|string|null $content = null): static`
- `p(Framework\View\Base\Element|string|null $content = null): static`
- `strong(Framework\View\Base\Element|string|null $content = null): static`
- `em(Framework\View\Base\Element|string|null $content = null): static`
- `small(Framework\View\Base\Element|string|null $content = null): static`
- `blockquote(Framework\View\Base\Element|string|null $content = null): static`
- `code(Framework\View\Base\Element|string|null $content = null): static`
- `pre(Framework\View\Base\Element|string|null $content = null): static`
- `fontBold(): static`
- `fontSemibold(): static`
- `textSm(): static`
- `textXs(): static`
- `textLg(): static`
- `textXl(): static`
- `text2xl(): static`
- `text3xl(): static`
- `textGray(string $shade = '500'): static`
- `textWhite(): static`
- `textCenter(): static`
- `textRight(): static`
- `truncate(): static`
- `uppercase(): static`
- `leading(string $size = 'normal'): static`

