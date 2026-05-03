# UX 组件库 — API 参考

> 由 DocGen 自动生成于 2026-05-03 15:49:15

## 目录

**其他**
- [`Accordion`](#framework-ux-ui-accordion) — 手风琴
- [`Alert`](#framework-ux-feedback-alert) — 提示框
- [`Avatar`](#framework-ux-display-avatar) — 头像
- [`Badge`](#framework-ux-display-badge) — 徽标
- [`BatchActionsMenu`](#framework-ux-data-batchactionsmenu) — 批量操作菜单
- [`Breadcrumb`](#framework-ux-navigation-breadcrumb) — 面包屑
- [`Button`](#framework-ux-ui-button) — 按钮
- [`Calendar`](#framework-ux-data-calendar) — Calendar 日历组件
- [`Card`](#framework-ux-display-card) — 卡片
- [`Carousel`](#framework-ux-media-carousel) — 轮播图
- [`Chart`](#framework-ux-chart-chart) — 图表
- [`Checkbox`](#framework-ux-form-checkbox) — 复选框
- [`Collapse`](#framework-ux-display-collapse) — 折叠面板
- [`ColorPicker`](#framework-ux-form-colorpicker) — 颜色选择器
- [`ConfirmDialog`](#framework-ux-dialog-confirmdialog) — 确认对话框
- [`DataCard`](#framework-ux-data-datacard) — 数据卡片
- [`DataGrid`](#framework-ux-data-datagrid) — 数据表格
- [`DataList`](#framework-ux-data-datalist) — 数据列表
- [`DataTable`](#framework-ux-data-datatable) — 数据表格
- [`DataTableColumn`](#framework-ux-data-datatablecolumn) — 数据表格列配置
- [`DataTree`](#framework-ux-data-datatree) — 树形数据
- [`DatePicker`](#framework-ux-form-datepicker) — 日期选择器
- [`DateRangePicker`](#framework-ux-form-daterangepicker) — 日期范围选择器
- [`DescriptionList`](#framework-ux-data-descriptionlist) — 描述列表
- [`Divider`](#framework-ux-display-divider) — 分割线
- [`DocumentParser`](#framework-ux-richeditor-documentparser) — 文档解析器
- [`Drawer`](#framework-ux-dialog-drawer) — 抽屉
- [`Dropdown`](#framework-ux-menu-dropdown) — 下拉菜单
- [`EmojiExtension`](#framework-ux-richeditor-extensions-emojiextension)
- [`EmptyState`](#framework-ux-feedback-emptystate) — 空状态
- [`ExtensionRegistry`](#framework-ux-richeditor-extensionregistry) — 扩展注册表
- [`FileUpload`](#framework-ux-form-fileupload) — 文件上传
- [`FormBuilder`](#framework-ux-form-formbuilder) — 表单构建器
- [`FormField`](#framework-ux-form-formfield) — 表单字段基类
- [`Grid`](#framework-ux-layout-grid) — 栅格布局
- [`Image`](#framework-ux-media-image) — 图片
- [`ImageUpload`](#framework-ux-form-imageupload) — 图片上传组件
- [`Input`](#framework-ux-form-input) — 输入框
- [`Layout`](#framework-ux-layout-layout) — 页面布局
- [`ListView`](#framework-ux-display-listview) — 列表
- [`LiveRichEditor`](#framework-ux-form-livericheditor) — 实时富文本编辑器
- [`LoadingOverlay`](#framework-ux-feedback-loadingoverlay) — 加载遮罩层组件
- [`MentionExtension`](#framework-ux-richeditor-extensions-mentionextension)
- [`Menu`](#framework-ux-menu-menu) — 菜单
- [`Modal`](#framework-ux-dialog-modal) — Modal 弹窗组件
- [`Navigate`](#framework-ux-ui-navigate) — 导航链接
- [`Pagination`](#framework-ux-navigation-pagination) — 分页
- [`PlaceholderExtension`](#framework-ux-richeditor-extensions-placeholderextension)
- [`Popover`](#framework-ux-overlay-popover) — 气泡卡片
- [`Progress`](#framework-ux-feedback-progress) — 进度条
- [`QRCode`](#framework-ux-display-qrcode) — 二维码
- [`Radio`](#framework-ux-form-radio) — 单选框
- [`RadioGroup`](#framework-ux-form-radiogroup) — 单选框组
- [`Rate`](#framework-ux-form-rate) — 评分组件
- [`RichEditor`](#framework-ux-form-richeditor) — 富文本编辑器
- [`Row`](#framework-ux-layout-row) — 行布局
- [`SearchInput`](#framework-ux-form-searchinput) — 搜索输入框
- [`Select`](#framework-ux-form-select) — 下拉选择框
- [`Skeleton`](#framework-ux-feedback-skeleton) — 骨架屏
- [`Slider`](#framework-ux-form-slider) — 滑块
- [`StatCard`](#framework-ux-display-statcard) — 统计卡片
- [`Steps`](#framework-ux-navigation-steps) — 步骤条
- [`SwitchField`](#framework-ux-form-switchfield) — 开关
- [`Tabs`](#framework-ux-navigation-tabs) — 标签页
- [`Tag`](#framework-ux-display-tag) — 标签
- [`TagInput`](#framework-ux-form-taginput) — 标签输入框
- [`Textarea`](#framework-ux-form-textarea) — 多行文本框
- [`Timeline`](#framework-ux-display-timeline) — 时间线
- [`Toast`](#framework-ux-dialog-toast) — 消息提示
- [`Tooltip`](#framework-ux-overlay-tooltip) — 提示框
- [`Transfer`](#framework-ux-form-transfer) — 穿梭框
- [`TreeSelect`](#framework-ux-form-treeselect) — 树形选择器
- [`UXComponent`](#framework-ux-uxcomponent) — UX 组件基类
- [`Watermark`](#framework-ux-display-watermark) — 水印

---

### 其他

<a name="framework-ux-ui-accordion"></a>
#### `Framework\UX\UI\Accordion`

手风琴

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/UI/Accordion.php`

**示例：**

```php
Accordion::make()->panel('面板1', '内容1')->panel('面板2', '内容2')
```

```php
Accordion::make()->panel('标题1', $view1)->panel('标题2', $view2)->allowMultiple()
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `item` | 添加面板项 | `mixed $title`, `mixed $content`, `?string $id` = null, `bool $open` = false |
| `multiple` | 允许多个面板同时展开 | `bool $multiple` = true |
| `variant` | 设置变体样式 | `string $variant` |
| `dark` | 启用暗色主题 | `bool $dark` = true |


<a name="framework-ux-feedback-alert"></a>
#### `Framework\UX\Feedback\Alert`

提示框

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Feedback/Alert.php`

**示例：**

```php
Alert::make()->message('操作成功')->success()
```

```php
Alert::make()->message('警告信息')->warning()->dismissible()
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `message` | 设置提示消息 | `string $message` |
| `type` | 设置提示类型 | `string $type` |
| `success` | 成功类型 | — |
| `error` | 错误类型 | — |
| `warning` | 警告类型 | — |
| `info` | 信息类型 | — |
| `dismissible` | 设置是否可关闭 | `bool $dismissible` = true |
| `title` | 设置标题 | `string $title` |
| `icon` | 设置图标 | `string $icon` |


<a name="framework-ux-display-avatar"></a>
#### `Framework\UX\Display\Avatar`

头像

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Display/Avatar.php`

**示例：**

```php
Avatar::make()->src('/user.jpg')->size('lg')
```

```php
Avatar::make()->name('张三')->circle()
```

```php
Avatar::make()->name('李四')->status('online')
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `src` | 设置头像图片源 | `string $src` |
| `name` | 设置用户姓名（用于显示首字母） | `string $name` |
| `size` | 设置头像尺寸 | `string $size` |
| `shape` | 设置头像形状 | `string $shape` |
| `circle` | 圆形头像 | — |
| `rounded` | 圆角头像 | — |
| `status` | 设置状态指示器 | `string $status` |


<a name="framework-ux-display-badge"></a>
#### `Framework\UX\Display\Badge`

徽标

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Display/Badge.php`

**示例：**

```php
Badge::make('99')->primary()
```

```php
Badge::make()->dot()->primary()
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `make` |  | `mixed $text` = '' |
| `variant` | 设置颜色变体 | `string $variant` |
| `default` | 默认变体 | — |
| `primary` | 主色变体 | — |
| `success` | 成功变体 | — |
| `warning` | 警告变体 | — |
| `danger` | 危险变体 | — |
| `info` | 信息变体 | — |
| `size` | 设置尺寸 | `string $size` |
| `sm` | 小尺寸 | — |
| `md` | 中等尺寸 | — |
| `lg` | 大尺寸 | — |
| `pill` | 胶囊形状 | `bool $pill` = true |
| `dot` | 圆点模式 | `bool $dot` = true |
| `text` | 设置显示文字 | `string $text` |


<a name="framework-ux-data-batchactionsmenu"></a>
#### `Framework\UX\Data\BatchActionsMenu`

批量操作菜单

**文件:** `php/src/UX/Data/BatchActionsMenu.php`

**示例：**

```php
BatchActionsMenu::make()->action('删除', 'batchDelete', 'danger')->action('导出', 'batchExport')
```

```php
BatchActionsMenu::make()->actions($actions)->selectedKeys($selected)->liveAction('handleBatch')
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `emptyText` | 设置空状态提示文字 | `string $text` |
| `selectCountText` | 设置选中计数显示文字 | `string $text` |
| `action` | 添加一个批量操作项 | `string $label`, `string $action`, `string $variant` = 'default', `?string $icon` = null, `?string $confirm` = null |
| `actions` | 批量添加操作项 | `array $actions` |
| `liveAction` | 设置 LiveAction | `string $action`, `string $event` = 'click' |
| `selectedKeys` | 设置已选中的行 key 列表 | `array $keys` |
| `visible` | 设置是否可见 | `bool $visible` = true |
| `render` |  | — |


<a name="framework-ux-navigation-breadcrumb"></a>
#### `Framework\UX\Navigation\Breadcrumb`

面包屑

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Navigation/Breadcrumb.php`

**示例：**

```php
Breadcrumb::make()->item('首页', '/')->item('分类', '/cat')->item('当前页')
```

```php
Breadcrumb::make()->item('Home')->item('Products')->item('Details')->separator('>')
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `item` | 添加面包屑项 | `string $label`, `?string $link` = null |
| `separator` | 设置分隔符 | `string $separator` |


<a name="framework-ux-ui-button"></a>
#### `Framework\UX\UI\Button`

按钮

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/UI/Button.php`

**示例：**

```php
Button::make()->label('提交')->primary()->liveAction('save')
```

```php
Button::make()->label('危险')->danger()->outline()
```

```php
Button::make()->label('带图标')->icon('pencil', 'left')->bi('edit')
```

```php
Button::make()->label('链接')->navigate('/page')
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `label` | 设置按钮文本 | `string $label` |
| `type` | 设置按钮类型 | `string $type` |
| `submit` | 设置为提交按钮 | — |
| `variant` | 设置颜色变体 | `string $variant` |
| `primary` | 主色变体 | — |
| `secondary` | 次色变体 | — |
| `danger` | 危险变体 | — |
| `success` | 成功变体 | — |
| `warning` | 警告变体 | — |
| `size` | 设置尺寸 | `string $size` |
| `sm` | 小尺寸 | — |
| `lg` | 大尺寸 | — |
| `icon` | 设置图标 | `string $icon`, `string $position` = 'left', `string $family` = 'bi' |
| `bi` | 使用 Bootstrap Icons | `string $name`, `string $position` = 'left' |
| `loading` | 设置加载状态 | `bool $loading` = true |
| `disabled` | 设置禁用状态 | `bool $disabled` = true |
| `outline` | 设置边框模式 | `bool $outline` = true |
| `block` | 设置块级按钮（全宽） | `bool $block` = true |
| `href` | 将按钮设置为链接模式 | `string $href` |
| `navigate` | 启用无刷新导航（data-navigate） | `string $url`, `?string $fragment` = null |
| `openModal` | 触发打开模态框事件 | `string $modalId` |
| `closeModal` | 触发关闭模态框事件 | `?string $modalId` = null |
| `showToast` | 触发显示 Toast 事件 | `string $message`, `string $type` = 'success' |


<a name="framework-ux-data-calendar"></a>
#### `Framework\UX\Data\Calendar`

Calendar 日历组件

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Data/Calendar.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `value` | 设置选中日期值 | `string $value` |
| `mode` | 设置视图模式 | `string $mode` |
| `month` | 月份视图 | — |
| `year` | 年份视图 | — |
| `fullscreen` | 设置全屏模式 | `bool $fullscreen` = true |
| `disabled` | 设置禁用状态 | `bool $disabled` = true |
| `action` | 设置日历选择动作 | `string $action` |
| `validRange` | 设置有效日期范围 | `string $start`, `string $end` |


<a name="framework-ux-display-card"></a>
#### `Framework\UX\Display\Card`

卡片

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Display/Card.php`

**示例：**

```php
Card::make()->title('标题')->subtitle('副标题')->child('内容')
```

```php
Card::make()->title('图片卡')->image('/img.jpg', 'top')->child('内容')
```

```php
Card::make()->title('带页脚')->footer(Button::make()->label('操作')->primary())
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `title` | 设置卡片标题 | `string $title` |
| `subtitle` | 设置卡片副标题 | `string $subtitle` |
| `header` | 设置自定义页眉内容 | `mixed $header` |
| `footer` | 设置自定义页脚内容 | `mixed $footer` |
| `image` | 设置卡片图片 | `string $src`, `string $position` = 'top' |
| `variant` | 设置卡片变体 | `string $variant` |
| `bordered` | 带边框变体 | — |
| `shadow` | 阴影变体 | — |
| `flat` | 扁平变体 | — |


<a name="framework-ux-media-carousel"></a>
#### `Framework\UX\Media\Carousel`

轮播图

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Media/Carousel.php`

**示例：**

```php
Carousel::make()->item('幻灯片1')->item('幻灯片2')->autoplay()
```

```php
Carousel::make()->items($slides)->dots()->arrows()->effect('fade')
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `item` | 添加单个幻灯片 | `string $content`, `?string $title` = null |
| `items` | 批量设置幻灯片 | `array $items` |
| `autoplay` | 启用自动播放 | `bool $autoplay` = true, `int $interval` = 3000 |
| `dots` | 显示指示点 | `bool $dots` = true |
| `arrows` | 显示箭头导航 | `bool $arrows` = true |
| `effect` | 设置切换效果 | `string $effect` |
| `fade` | 淡入淡出效果 | — |
| `loop` | 启用循环播放 | `bool $loop` = true |
| `action` | 设置 LiveAction（点击幻灯片触发） | `string $action` |


<a name="framework-ux-chart-chart"></a>
#### `Framework\UX\Chart\Chart`

图表

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Chart/Chart.php`

**示例：**

```php
Chart::make()->type('line')->data($data)->options(['responsive' => true])
```

```php
Chart::make()->type('bar')->dataset('销售', [10, 20, 30])->dataset('利润', [5, 15, 25])
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `type` | 设置图表类型 | `string $type` |
| `labels` | 设置图表标签（X 轴） | `array $labels` |
| `dataset` | 添加数据集 | `string $label`, `array $data`, `array $options` = [] |
| `chartData` | 设置完整图表数据（替代 labels + datasets） | `array $chartData` |
| `options` | 设置图表选项（底层 Chart.js 配置） | `array $options` |
| `title` | 设置图表标题 | `string $title` |
| `description` | 设置图表描述/副标题 | `string $description` |
| `height` | 设置图表高度 | `int $height` |
| `showLegend` | 设置是否显示图例 | `bool $show` = true |
| `showGrid` | 设置是否显示网格线 | `bool $show` = true |
| `animation` | 设置动画效果 | `string $animation` |


<a name="framework-ux-form-checkbox"></a>
#### `Framework\UX\Form\Checkbox`

复选框

**继承:** `Framework\UX\Form\FormField`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Form/Checkbox.php`

**示例：**

```php
Checkbox::make()->label('同意协议')->model('agree')
```

```php
Checkbox::make()->label('已读')->checked()
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `checked` | 设置选中状态 | `bool $checked` = true |


<a name="framework-ux-display-collapse"></a>
#### `Framework\UX\Display\Collapse`

折叠面板

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Display/Collapse.php`

**示例：**

```php
Collapse::make()->title('面板1')->child('内容1')
```

```php
Collapse::make()->title('默认展开')->open()->child('内容')
```

```php
Collapse::make()->title('带图标')->icon('bi-chevron-down')->child('内容')
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `title` | 设置面板标题 | `string $title` |
| `open` | 设置默认展开状态 | `bool $open` = true |
| `disabled` | 设置禁用状态 | `bool $disabled` = true |
| `icon` | 设置展开/折叠图标 | `string $icon` |
| `action` | 设置 LiveAction（点击时触发） | `string $action` |


<a name="framework-ux-form-colorpicker"></a>
#### `Framework\UX\Form\ColorPicker`

颜色选择器

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Form/ColorPicker.php`

**示例：**

```php
ColorPicker::make()->value('#3b82f6')->label('主题色')
```

```php
ColorPicker::make()->value('#ef4444')->presets(['#ef4444', '#3b82f6', '#10b981'])
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `value` | 设置颜色值 | `string $value` |
| `allowClear` | 启用清除按钮 | `bool $allow` = true |
| `disabled` | 设置禁用状态 | `bool $disabled` = true |
| `showText` | 显示颜色值文本 | `bool $show` = true |
| `action` | 设置 LiveAction（选择颜色时触发） | `string $action` |
| `presets` | 设置预设颜色列表 | `array $presets` |


<a name="framework-ux-dialog-confirmdialog"></a>
#### `Framework\UX\Dialog\ConfirmDialog`

确认对话框

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Dialog/ConfirmDialog.php`

**示例：**

```php
ConfirmDialog::make()->title('确认删除')->message('确定要删除吗？')->okText('删除')->okVariant('danger')
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `title` | 设置对话框标题 | `string $title` |
| `message` | 设置确认消息 | `string $message` |
| `okText` | 设置确定按钮文字 | `string $text` |
| `cancelText` | 设置取消按钮文字 | `string $text` |
| `okVariant` | 设置确定按钮颜色变体 | `string $variant` |
| `cancelVariant` | 设置取消按钮颜色变体 | `string $variant` |
| `open` | 设置对话框打开状态 | `bool $open` = true |
| `close` | 关闭对话框 | — |


<a name="framework-ux-data-datacard"></a>
#### `Framework\UX\Data\DataCard`

数据卡片

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Data/DataCard.php`

**示例：**

```php
DataCard::make()->title('用户信息')->field('姓名', 'name')->field('邮箱', 'email')->dataSource($user)
```

```php
DataCard::make()->cover('/cover.jpg')->avatar($avatar)->title('详情')->fields($fields)->item($data)
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `field` | 添加一个字段 | `string $label`, `string $dataKey`, `?Closure $render` = null, `array $options` = [] |
| `fields` | 批量添加字段 | `array $fields` |
| `dataSource` | 设置数据源 | `array $data` |
| `item` | 设置数据项（别名） | `array $data` |
| `variant` | 设置变体 | `string $variant` |
| `title` | 设置卡片标题 | `string $title` |
| `subtitle` | 设置卡片副标题 | `string $subtitle` |
| `avatar` | 设置头像内容 | `mixed $avatar` |
| `actions` | 设置操作区内容 | `mixed $actions` |
| `cover` | 设置封面图 | `mixed $cover` |
| `bordered` | 设置是否带边框 | `bool $bordered` = true |
| `fragment` | 设置分片名称（用于 Live 局部更新） | `string $name` |


<a name="framework-ux-data-datagrid"></a>
#### `Framework\UX\Data\DataGrid`

数据表格

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Data/DataGrid.php`

**示例：**

```php
DataGrid::make()->columns($columns)->rows($users)->sortable()->pagination()
```

```php
DataGrid::make()->columns(['name' => '姓名', 'email' => '邮箱'])->rows($data)->searchable()
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `dataSource` | 设置数据源 | `array $data` |
| `rows` | 设置数据行（别名） | `array $data` |
| `renderItem` | 自定义每行渲染回调 | `Closure $callback` |
| `cols` | 设置列数 | `int $cols` |
| `gap` | 设置网格间距（单位：rem） | `int $gap` |
| `variant` | 设置变体 | `string $variant` |
| `emptyText` | 设置空数据提示文字 | `string $text` |
| `header` | 设置自定义头部内容 | `mixed $header` |
| `footer` | 设置自定义底部内容 | `mixed $footer` |
| `title` | 设置网格标题 | `string $title` |
| `pagination` | 设置分页 | `int $total`, `int $current` = 1, `int $perPage` = 15, `string $baseUrl` = '' |
| `fragment` | 设置分片名称（用于 Live 局部更新） | `string $name` |
| `itemAction` | 设置行点击动作 | `string $action`, `string $event` = 'click' |
| `pageAction` | 设置分页动作 | `string $action` |


<a name="framework-ux-data-datalist"></a>
#### `Framework\UX\Data\DataList`

数据列表

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Data/DataList.php`

**示例：**

```php
DataList::make()->columns(['name' => '姓名', 'age' => '年龄'])->rows($users)
```

```php
DataList::make()->columns($cols)->rows($data)->sortable()->pagination()
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `dataSource` | 设置数据源 | `array $data` |
| `rows` | 设置数据行（别名） | `array $data` |
| `renderItem` | 自定义每行渲染回调 | `Closure $callback` |
| `variant` | 设置列表变体 | `string $variant` |
| `size` | 设置列表尺寸 | `string $size` |
| `sm` | 小尺寸 | — |
| `lg` | 大尺寸 | — |
| `bordered` | 设置是否带边框 | `bool $bordered` = true |
| `split` | 设置是否分隔线样式 | `bool $split` = true |
| `emptyText` | 设置空数据提示文字 | `string $text` |
| `header` | 设置自定义头部内容 | `mixed $header` |
| `footer` | 设置自定义底部内容 | `mixed $footer` |
| `title` | 设置列表标题 | `string $title` |
| `pagination` | 设置分页 | `int $total`, `int $current` = 1, `int $perPage` = 15, `string $baseUrl` = '' |
| `fragment` | 设置分片名称（用于 Live 局部更新） | `string $name` |
| `itemAction` | 设置行点击动作 | `string $action`, `string $event` = 'click' |
| `pageAction` | 设置分页动作 | `string $action` |


<a name="framework-ux-data-datatable"></a>
#### `Framework\UX\Data\DataTable`

数据表格

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Data/DataTable.php`

**示例：**

```php
DataTable::make()->columns($columns)->rows($users)->sortable()->pagination(100)
```

```php
DataTable::make()->columns(['name' => '姓名', 'email' => '邮箱'])->rows($data)->searchable()->selectable()
```

```php
DataTable::make()->columns($cols)->rows($items)->actions(['edit' => 'editRow', 'delete' => 'deleteRow'])
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `column` | 添加一列 | `string $dataKey`, `string $title`, `?Closure $render` = null, `array $options` = [] |
| `addColumn` | 使用 DataTableColumn 对象添加列（链式） | `Framework\UX\Data\DataTableColumn $column` |
| `getSearchableColumns` | 获取可搜索的列 | — |
| `columns` | 批量设置列 | `array $columns` |
| `dataSource` | 设置数据源 | `array $data` |
| `rows` | 设置数据行（别名） | `array $data` |
| `rowKey` | 设置行键名 | `string $key` |
| `size` | 设置表格尺寸 | `string $size` |
| `sm` | 小尺寸 | — |
| `lg` | 大尺寸 | — |
| `striped` | 设置斑马纹 | `bool $striped` = true |
| `bordered` | 设置是否带边框 | `bool $bordered` = true |
| `hoverable` | 设置是否悬停高亮 | `bool $hoverable` = true |
| `selectable` | 设置是否可选（单选/多选） | `bool $selectable` = true |
| `emptyText` | 设置空数据提示文字 | `string $text` |
| `header` | 设置自定义头部内容 | `mixed $header` |
| `footer` | 设置自定义底部内容 | `mixed $footer` |
| `title` | 设置表格标题 | `string $title` |
| `pagination` | 设置分页 | `int $total`, `int $current` = 1, `int $perPage` = 15, `string $baseUrl` = '' |
| `paginationComponent` | 设置自定义分页组件 | `Framework\UX\Navigation\Pagination $pagination` |
| `rowAttr` | 设置行属性 | `string $key`, `string $value` |
| `rowCallback` | 设置行回调（动态添加类名等） | `Closure $callback` |
| `sortField` | 设置当前排序字段 | `?string $field` |
| `sortDirection` | 设置排序方向 | `string $direction` |
| `fragment` | 设置分片名称（用于 Live 局部更新） | `string $name` |
| `sortAction` | 设置排序动作 | `string $action` |
| `pageAction` | 设置分页动作 | `string $action` |
| `selectAction` | 设置选择动作 | `string $action` |
| `registerAction` | 注册单个动作 | `string $name`, `string $action`, `string $event` = 'click', `array $config` = [] |
| `actions` | 批量注册动作 | `array $actions` |
| `searchable` | 设置是否可搜索 | `bool $searchable` = true |
| `searchAction` | 设置搜索动作 | `string $action` |
| `searchValue` | 设置搜索值 | `?string $value` |
| `searchPlaceholder` | 设置搜索框占位符 | `string $placeholder` |
| `batchActions` | 设置批量操作列表 | `array $actions` |
| `batchAction` | 设置批量操作动作 | `string $action` |
| `perPageOptions` | 设置每页条数选项 | `array $options` |
| `perPageAction` | 设置每页条数动作 | `string $action` |
| `showPerPage` | 显示每页条数选择器 | `bool $show` = true, `int $total` = 0, `int $perPage` = 15, `int $page` = 1 |
| `tooltip` | 设置提示回调 | `?Closure $callback` |
| `rowActions` | 注册行操作组件 闭包接收 ($row, $rowKey, $rowIndex) 返回组件数组 | `Closure $callback` |
| `editable` | 启用行内编辑 | `array $columns` = [], `string $action` = 'saveEdit' |
| `editType` | 设置编辑类型 | `string $type` |
| `getEditableColumns` | 获取可编辑的列配置 | — |
| `isColumnEditable` | 检查列是否可编辑 | `string $columnKey` |


<a name="framework-ux-data-datatablecolumn"></a>
#### `Framework\UX\Data\DataTableColumn`

数据表格列配置

**文件:** `php/src/UX/Data/DataTableColumn.php`

**示例：**

```php
DataTableColumn::make('name', '姓名')->sortable()->alignCenter()
```

```php
DataTableColumn::make('action', '操作')->render(fn($row) => Button::make()->label('编辑')->liveAction('edit', $row['id']))
```

```php
DataTableColumn::make('created_at', '创建时间')->searchable()->searchLike()
```

**属性：**

| 属性 | 类型 | 说明 |
|---|---|---|
| `$dataKey` | `string` |  |
| `$title` | `string` |  |
| `$render` | `?Closure` = null |  |
| `$width` | `?string` = null |  |
| `$align` | `?string` = null |  |
| `$sortable` | `bool` = false |  |
| `$fixed` | `?string` = null |  |
| `$visible` | `bool` = true |  |
| `$tooltip` | `?string` = null |  |
| `$searchable` | `bool` = false |  |
| `$searchType` | `string` = 'like' |  |
| `$searchOptions` | `?array` = null |  |

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `make` |  | `string $dataKey`, `string $title` |
| `width` | 设置列宽 | `?string $width` |
| `align` | 设置列对齐方式 | `?string $align` |
| `alignCenter` | 居中对齐 | — |
| `alignRight` | 右对齐 | — |
| `alignLeft` | 左对齐 | — |
| `sortable` | 设置是否可排序 | `bool $sortable` = true |
| `fixed` | 设置列固定位置 | `?string $position` |
| `fixedLeft` | 固定在左侧 | — |
| `fixedRight` | 固定在右侧 | — |
| `visible` | 设置列是否可见 | `bool $visible` = true |
| `hidden` | 隐藏列 | — |
| `tooltip` | 设置列提示文字 | `?string $tooltip` |
| `render` | 自定义列渲染 | `Closure $callback` |
| `searchable` | 设置列是否可搜索 | `bool $searchable` = true, `string $type` = 'like', `?array $options` = null |
| `searchEqual` | 精确搜索（=） | — |
| `searchLike` | 模糊搜索（like） | — |
| `searchIn` | IN 搜索（多选） | `?array $options` = null |
| `toArray` |  | — |


<a name="framework-ux-data-datatree"></a>
#### `Framework\UX\Data\DataTree`

树形数据

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Data/DataTree.php`

**示例：**

```php
DataTree::make()->treeData($tree)->showIcon()->showLine()
```

```php
DataTree::make()->treeData($deptTree)->selectable()->selectAction('selectDept')
```

```php
DataTree::make()->treeData($files)->checkable()->checkAction('checkFiles')->defaultExpandAll()
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `treeData` | 设置树形数据 | `array $data` |
| `renderTitle` | 自定义节点标题渲染 | `Closure $callback` |
| `variant` | 设置树变体 | `string $variant` |
| `showLine` | 设置是否显示连接线 | `bool $showLine` = true |
| `showIcon` | 设置是否显示图标 | `bool $showIcon` = true |
| `selectable` | 设置是否可选（单选） | `bool $selectable` = true |
| `checkable` | 设置是否可勾选（多选） | `bool $checkable` = true |
| `defaultExpandAll` | 设置是否默认展开所有节点 | `bool $expand` = true |
| `defaultExpandedKeys` | 设置默认展开的节点 key 列表 | `array $keys` |
| `emptyText` | 设置空数据提示文字 | `string $text` |
| `title` | 设置树标题 | `string $title` |
| `header` | 设置自定义头部内容 | `mixed $header` |
| `fragment` | 设置分片名称（用于 Live 局部更新） | `string $name` |
| `toggleAction` | 设置节点展开/折叠动作 | `string $action` |
| `selectAction` | 设置节点选择动作 | `string $action` |
| `checkAction` | 设置节点勾选动作 | `string $action` |


<a name="framework-ux-form-datepicker"></a>
#### `Framework\UX\Form\DatePicker`

日期选择器

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Form/DatePicker.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `value` | 设置默认日期值 | `string $value` |
| `placeholder` | 设置占位文本 | `string $placeholder` |
| `format` | 设置日期格式 | `string $format` |
| `disabled` | 设置禁用状态 | `bool $disabled` = true |
| `allowClear` | 是否允许清除 | `bool $allow` = true |
| `showToday` | 显示"今天"快捷按钮 | `bool $show` = true |
| `minDate` | 设置最小可选日期 | `string $date` |
| `maxDate` | 设置最大可选日期 | `string $date` |
| `action` | 设置 LiveAction（已废弃，请用 liveModel） | `string $action` |
| `showTime` | 启用时间选择，格式自动切换为 Y-m-d H:i:s | `bool $show` = true |
| `timeHour` | 设置默认小时 | `int $hour` |
| `timeMinute` | 设置默认分钟 | `int $minute` |
| `timeSecond` | 设置默认秒数 | `int $second` |


<a name="framework-ux-form-daterangepicker"></a>
#### `Framework\UX\Form\DateRangePicker`

日期范围选择器

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Form/DateRangePicker.php`

**示例：**

```php
DateRangePicker::make()->value('2026-01-01', '2026-01-31')->placeholder('选择日期范围')
```

```php
DateRangePicker::make()->minDate('2026-01-01')->maxDate('2026-12-31')->showTime()
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `startValue` | 设置开始日期 | `string $value` |
| `endValue` | 设置结束日期 | `string $value` |
| `value` | 设置日期范围 | `string $start`, `string $end` |
| `placeholder` | 设置占位文本 | `string $placeholder` |
| `format` | 设置日期格式 | `string $format` |
| `disabled` | 设置禁用状态 | `bool $disabled` = true |
| `allowClear` | 是否允许清除 | `bool $allow` = true |
| `showToday` | 显示"今天"快捷按钮 | `bool $show` = true |
| `minDate` | 设置最小可选日期 | `string $date` |
| `maxDate` | 设置最大可选日期 | `string $date` |
| `action` | 设置 LiveAction（已废弃，请用 liveModel） | `string $action` |
| `separator` | 设置分隔符 | `string $separator` |
| `showTime` | 启用时间选择，格式自动切换为 Y-m-d H:i:s | `bool $show` = true |


<a name="framework-ux-data-descriptionlist"></a>
#### `Framework\UX\Data\DescriptionList`

描述列表

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Data/DescriptionList.php`

**示例：**

```php
DescriptionList::make()->item('姓名', '张三')->item('邮箱', 'test@example.com')->columns(2)
```

```php
DescriptionList::make()->title('详细信息')->items($items)->bordered()->labelAlign('left')
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `item` | 添加一个描述项 | `string $label`, `mixed $value`, `?Closure $render` = null |
| `items` | 批量添加描述项 | `array $items` |
| `columns` | 设置列数 | `int $columns` |
| `variant` | 设置变体 | `string $variant` |
| `size` | 设置尺寸 | `string $size` |
| `sm` | 小尺寸 | — |
| `lg` | 大尺寸 | — |
| `bordered` | 设置是否带边框 | `bool $bordered` = true |
| `title` | 设置标题 | `string $title` |
| `extra` | 设置额外内容（标题右侧） | `mixed $extra` |
| `labelAlign` | 设置标签对齐方式 | `string $align` |
| `fragment` | 设置分片名称（用于 Live 局部更新） | `string $name` |


<a name="framework-ux-display-divider"></a>
#### `Framework\UX\Display\Divider`

分割线

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Display/Divider.php`

**示例：**

```php
Divider::make()->text('或者')
```

```php
Divider::make()->vertical()->dashed()
```

```php
Divider::make()->orientationLeft()->text('标题')->primary()
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `text` | 设置分割线文字 | `string $text` |
| `orientation` | 设置文字位置（仅水平模式） | `string $orientation` |
| `orientationLeft` | 文字居左 | — |
| `orientationRight` | 文字居右 | — |
| `orientationCenter` | 文字居中 | — |
| `vertical` | 垂直分割线 | — |
| `horizontal` | 水平分割线 | — |
| `dashed` | 虚线样式 | `bool $dashed` = true |
| `variant` | 设置颜色变体 | `string $variant` |
| `primary` | 主色变体 | — |
| `success` | 成功变体 | — |
| `warning` | 警告变体 | — |
| `danger` | 危险变体 | — |


<a name="framework-ux-richeditor-documentparser"></a>
#### `Framework\UX\RichEditor\DocumentParser`

文档解析器

**文件:** `php/src/UX/RichEditor/DocumentParser.php`

**示例：**

```php
DocumentParser::htmlToMarkdown('<p>Hello <strong>World</strong></p>')
```

```php
DocumentParser::markdownToHtml('# Title\n\n**Bold** text')
```

```php
DocumentParser::sanitize($html, '<p><strong><em>')
```

```php
DocumentParser::wordCount($content)
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `htmlToText` | HTML 转纯文本（移除所有标签） | `string $html` |
| `htmlToMarkdown` | HTML 转 Markdown | `string $html` |
| `markdownToHtml` | Markdown 转 HTML | `string $markdown` |
| `extractPlainText` | 提取纯文本（可选截断） | `string $html`, `int $maxLength` = 0 |
| `truncateHtml` | 截断 HTML 内容（基于纯文本长度） | `string $html`, `int $maxLength`, `string $suffix` = '...' |
| `sanitize` | 清洗 HTML 内容（移除危险标签和脚本） | `string $html`, `array\|string\|null $allowedTags` = null |
| `wordCount` | 统计字数（去除标点和空格） | `string $content` |
| `characterCount` | 统计字符数 | `string $content`, `bool $includeSpaces` = true |
| `registerProcessor` | 注册内容处理器 | `string $name`, `callable $processor` |
| `registerFilter` | 注册内容过滤器 | `string $name`, `callable $filter` |
| `process` | 按顺序执行处理器 | `string $content`, `array $processorNames` = [] |
| `filter` | 按顺序执行过滤器 | `string $content`, `array $filterNames` = [] |


<a name="framework-ux-dialog-drawer"></a>
#### `Framework\UX\Dialog\Drawer`

抽屉

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Dialog/Drawer.php`

**示例：**

```php
Drawer::make()->title('侧边栏')->right()->child('内容')
```

```php
Drawer::make()->title('详情')->size('lg')->left()->child($view)
```

```php
Drawer::make()->title('顶部')->top()->child('内容')->trigger('打开抽屉')
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `title` | 设置抽屉标题 | `string $title` |
| `child` | 添加抽屉内容 | `mixed $child` |
| `position` | 设置抽屉位置 | `string $position` |
| `left` | 左侧抽屉 | — |
| `right` | 右侧抽屉 | — |
| `top` | 顶部抽屉 | — |
| `bottom` | 底部抽屉 | — |
| `size` | 设置抽屉尺寸 | `string $size` |
| `sm` | 小尺寸 | — |
| `md` | 中等尺寸 | — |
| `lg` | 大尺寸 | — |
| `xl` | 超大尺寸 | — |
| `full` | 全屏尺寸 | — |
| `open` | 设置打开状态 | `bool $open` = true |
| `trigger` | 生成触发按钮 | `string $label`, `string $variant` = 'primary' |


<a name="framework-ux-menu-dropdown"></a>
#### `Framework\UX\Menu\Dropdown`

下拉菜单

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Menu/Dropdown.php`

**示例：**

```php
Dropdown::make()->trigger(Button::make()->label('菜单')->primary())->item('选项1')->item('选项2')
```

```php
Dropdown::make()->trigger('更多')->item('编辑')->divider()->item('删除')->danger()
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `label` | 设置默认标签文字（当不使用自定义 trigger 时显示） | `string $label` |
| `item` | 添加菜单项 | `string $label`, `?string $url` = '#', `?string $icon` = null, `?string $action` = null, `array $params` = [] |
| `noborder` | 设置无边框样式 | `bool $noborder` = true |
| `element` | 传入自定义 Element 作为菜单项（最灵活） | `mixed $content` |
| `divider` | 添加分隔线 | — |
| `position` | 设置菜单弹出位置 | `string $position` |
| `hover` | 启用悬停触发 | `bool $hover` = true |
| `customTrigger` | 自定义 trigger 元素（替代默认的文字按钮） | `mixed $trigger` |


<a name="framework-ux-richeditor-extensions-emojiextension"></a>
#### `Framework\UX\RichEditor\Extensions\EmojiExtension`

**继承:** `Framework\UX\RichEditor\RichEditorExtension`  | **文件:** `php/src/UX/RichEditor/Extensions/EmojiExtension.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `getName` |  | — |
| `setEmojiMap` | 设置表情映射表 | `array $map` |
| `execute` | 执行表情插入 | `string $content`, `array $params` = [] |
| `parse` | 解析表情短代码为 emoji | `string $content` |
| `renderPreview` | 渲染预览内容（解析表情短代码） | `string $content` |
| `getAvailableEmojis` | 获取可用表情列表 | — |
| `getToolbarButton` |  | `string $editorId` |


<a name="framework-ux-feedback-emptystate"></a>
#### `Framework\UX\Feedback\EmptyState`

空状态

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Feedback/EmptyState.php`

**示例：**

```php
EmptyState::make()->description('暂无数据')->extra(Button::make()->label('添加')->primary())
```

```php
EmptyState::make()->image('/empty.svg')->description('没有找到结果')
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `description` | 设置空状态描述文字 | `string $description` |
| `image` | 设置空状态图片 | `string $image` |
| `imageStyle` | 设置图片样式 | `string $style` |
| `extra` | 设置额外内容（按钮/链接等） | `mixed $extra` |
| `size` | 设置尺寸 | `string $size` |
| `sm` | 小尺寸 | — |
| `lg` | 大尺寸 | — |


<a name="framework-ux-richeditor-extensionregistry"></a>
#### `Framework\UX\RichEditor\ExtensionRegistry`

扩展注册表

**文件:** `php/src/UX/RichEditor/ExtensionRegistry.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `register` | 注册扩展 | `string $name`, `Framework\UX\RichEditor\RichEditorExtension $extension` |
| `get` | 获取扩展 | `string $name` |
| `has` | 检查扩展是否存在 | `string $name` |
| `all` | 获取所有注册扩展 | — |
| `remove` | 移除扩展 | `string $name` |
| `registerParser` | 注册解析器 | `string $name`, `callable $parser` |
| `getParser` | 获取解析器 | `string $name` |
| `registerFormatter` | 注册格式化器 | `string $format`, `callable $formatter` |
| `getFormatter` | 获取格式化器 | `string $format` |
| `parseWith` | 使用解析器或扩展解析内容 | `string $content`, `string $parserName` |
| `formatAs` | 使用格式化器格式化内容 | `string $content`, `string $format` |
| `clear` | 清空所有注册 | — |


<a name="framework-ux-form-fileupload"></a>
#### `Framework\UX\Form\FileUpload`

文件上传

**继承:** `Framework\UX\Form\FormField`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Form/FileUpload.php`

**示例：**

```php
FileUpload::make()->name('avatar')->label('头像')->images()
```

```php
FileUpload::make()->name('files')->label('附件')->multiple()->documents()->maxSize(5120)
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `multiple` | 启用多选模式 | `bool $multiple` = true |
| `accept` | 设置接受的文件类型 | `string $accept` |
| `images` | 仅接受图片文件 | — |
| `documents` | 仅接受文档文件 | — |
| `maxSize` | 设置最大文件大小（KB） | `int $kb` |


<a name="framework-ux-form-formbuilder"></a>
#### `Framework\UX\Form\FormBuilder`

表单构建器

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Form/FormBuilder.php`

**示例：**

```php
FormBuilder::make()->post()->action('/save')->text('name', '姓名')->email('email', '邮箱')->submitLabel('提交')
```

```php
FormBuilder::make()->get()->text('q', '搜索')->submitLabel('搜索')
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `method` | 设置表单提交方法 | `string $method` |
| `get` | 设置为 GET 方法 | — |
| `post` | 设置为 POST 方法 | — |
| `put` | 设置为 PUT 方法 | — |
| `delete` | 设置为 DELETE 方法 | — |
| `action` | 设置表单提交地址 | `string $action` |
| `multipart` | 启用 multipart（用于文件上传） | `bool $multipart` = true |
| `submitLabel` | 设置提交按钮文字 | `string $label` |
| `text` | 添加文本输入框字段 | `string $name`, `string $label`, `array $options` = [] |
| `email` | 添加邮箱输入框字段 | `string $name`, `string $label`, `array $options` = [] |
| `password` | 添加密码输入框字段 | `string $name`, `string $label`, `array $options` = [] |
| `number` | 添加数字输入框字段 | `string $name`, `string $label`, `array $options` = [] |
| `textarea` | 添加多行文本框字段 | `string $name`, `string $label`, `array $options` = [] |
| `richEditor` | 添加富文本编辑器字段 | `string $name`, `string $label`, `array $options` = [] |
| `select` | 添加下拉选择框字段 | `string $name`, `string $label`, `array $options` = [], `array $selectOptions` = [] |
| `checkbox` | 添加复选框字段 | `string $name`, `string $label`, `array $options` = [] |
| `radio` | 添加单选框字段 | `string $name`, `string $label`, `array $choices`, `array $options` = [] |
| `file` | 添加文件上传字段 | `string $name`, `string $label`, `array $options` = [] |
| `hidden` | 添加隐藏字段 | `string $name`, `string $value` |
| `liveBind` | 绑定字段到 LiveComponent 属性 | `string $field`, `string $property` |
| `fill` | 填充表单数据 | `array $data` |


<a name="framework-ux-form-formfield"></a>
#### `Framework\UX\Form\FormField`

表单字段基类

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **abstract**  | **文件:** `php/src/UX/Form/FormField.php`

**示例：**

```php
Input::make()->name('username')->label('用户名')->required()
```

```php
Input::make()->name('email')->label('邮箱')->liveModel('email')->error('邮箱格式错误')
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `name` | 设置字段名称 | `string $name` |
| `label` | 设置标签文字 | `string $label` |
| `required` | 设置必填状态 | `bool $required` = true |
| `value` | 设置默认值 | `mixed $value` |
| `placeholder` | 设置占位文本 | `string $placeholder` |
| `help` | 设置帮助文本 | `string $help` |
| `disabled` | 设置禁用状态 | `bool $disabled` = true |
| `readonly` | 设置只读状态 | `bool $readonly` = true |
| `autocomplete` | 设置自动完成属性 | `string $autocomplete` |
| `rules` | 设置验证规则 | `array $rules` |
| `liveModel` | 绑定 Live 属性 | `string $property` |
| `error` | 设置验证错误信息 | `?string $message` = null |
| `invalid` | 设置无效状态（不显示错误信息） | `bool $invalid` = true |
| `getError` | 获取错误信息 | — |
| `isInvalid` | 是否处于无效状态 | — |
| `getName` |  | — |


<a name="framework-ux-layout-grid"></a>
#### `Framework\UX\Layout\Grid`

栅格布局

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Layout/Grid.php`

**示例：**

```php
Grid::make()->cols(3)->gap(4)->child($col1)->child($col2)->child($col3)
```

```php
Grid::make()->cols(4)->gap(6)->alignCenter()
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `cols` | 设置列数 | `int $cols` |
| `gap` | 设置列间距 | `int $gap` |
| `align` | 设置列对齐方式 | `string $align` |
| `alignStart` | 左对齐 | — |
| `alignCenter` | 居中对齐 | — |
| `alignEnd` | 右对齐 | — |


<a name="framework-ux-media-image"></a>
#### `Framework\UX\Media\Image`

图片

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Media/Image.php`

**示例：**

```php
Image::make()->src('/photo.jpg')->width(300)->height(200)
```

```php
Image::make()->src('/photo.jpg')->preview()->lazy()
```

```php
Image::make()->src('/photo.jpg')->fit('cover')->fallback('/fallback.jpg')
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `src` | 设置图片源地址 | `string $src` |
| `alt` | 设置图片替代文本 | `string $alt` |
| `width` | 设置图片宽度 | `int $width` |
| `height` | 设置图片高度 | `int $height` |
| `preview` | 启用预览（点击放大） | `bool $preview` = true |
| `lazy` | 启用懒加载 | `bool $lazy` = true |
| `fallback` | 设置失败替换图片 | `string $fallback` |
| `fit` | 设置适配模式 | `string $fit` |
| `contain` | 等比适配（保留宽高比，可能有留白） | — |
| `cover` | 覆盖适配（填满容器，可能裁剪） | — |
| `scaleDown` | 缩放适配（不超过原始尺寸） | — |


<a name="framework-ux-form-imageupload"></a>
#### `Framework\UX\Form\ImageUpload`

图片上传组件

**继承:** `Framework\UX\Form\FormField`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Form/ImageUpload.php`

**示例：**

```php
ImageUpload::make()->name('avatar')->label('头像')
```

```php
ImageUpload::make()->name('images')->label('图片集')->maxFiles(5)->multiple()
```

```php
ImageUpload::make()->name('cover')->label('封面')->croppable()->aspectRatio(16, 9)
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `multiple` | 设置多选模式 | `bool $multiple` = true |
| `maxFiles` | 设置最大上传文件数 | `int $max` |
| `acceptedFormats` | 设置允许的图片格式 | `array $formats` |
| `maxSize` | 设置最大文件大小（KB） | `int $kb` |
| `thumbnails` | 配置缩略图尺寸 | `array $thumbnails` |
| `croppable` | 启用图片裁剪 | `bool $croppable` = true |
| `aspectRatio` | 设置裁剪宽高比 | `float $ratio` |
| `minWidth` | 设置最小裁剪宽度 | `int $width` |
| `minHeight` | 设置最小裁剪高度 | `int $height` |
| `maxWidth` | 设置最大裁剪宽度 | `int $width` |
| `maxHeight` | 设置最大裁剪高度 | `int $height` |
| `showPreview` | 设置是否显示预览 | `bool $show` = true |
| `storage` | 设置存储方式 | `string $storage` |
| `bucket` | 设置存储桶名称（云存储） | `string $bucket` |
| `path` | 设置存储路径 | `string $path` |
| `cdnUrl` | 设置 CDN 地址 | `string $url` |
| `uploadAction` | 设置上传接口地址 | `string $action` |
| `viewMode` | 设置视图模式 | `string $mode` |
| `draggable` | 启用拖拽排序 | `bool $draggable` = true |


<a name="framework-ux-form-input"></a>
#### `Framework\UX\Form\Input`

输入框

**继承:** `Framework\UX\Form\FormField`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Form/Input.php`

**示例：**

```php
Input::make()->label('用户名')->model('username')
```

```php
Input::make()->label('邮箱')->email()->model('email')
```

```php
Input::make()->label('密码')->password()
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `type` | 设置输入类型 | `string $type` |
| `email` | 设置为邮箱类型 | — |
| `password` | 设置为密码类型 | — |
| `number` | 设置为数字类型 | — |
| `date` | 设置为日期类型 | — |
| `datetime` | 设置为日期时间类型 | — |
| `time` | 设置为时间类型 | — |
| `url` | 设置为 URL 类型 | — |
| `tel` | 设置为电话类型 | — |
| `search` | 设置为搜索类型 | — |
| `color` | 设置为颜色类型 | — |
| `range` | 设置为范围类型 | — |


<a name="framework-ux-layout-layout"></a>
#### `Framework\UX\Layout\Layout`

页面布局

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Layout/Layout.php`

**示例：**

```php
Layout::make()->header()->sidebar()->footer()->child($main)
```

```php
Layout::make()->header(true)->sidebarLeft()->sidebarWidth(240)->footer(true)
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `sidebar` | 启用侧边栏 | `bool $left` = true |
| `sidebarLeft` | 启用左侧边栏 | — |
| `sidebarRight` | 启用右侧边栏 | — |
| `sidebarWidth` | 设置侧边栏宽度 | `int $width` |
| `header` | 启用页眉 | `bool $fixed` = false |
| `footer` | 启用页脚 | `bool $fixed` = false |
| `renderHeader` | 渲染页眉 | `mixed $content` |
| `renderSidebar` | 渲染侧边栏 | `mixed $content` |
| `renderMain` | 渲染主内容区 | `mixed $content` |
| `renderFooter` | 渲染页脚 | `mixed $content` |
| `renderBody` | 渲染主体（侧边栏 + 主内容） | `mixed $sidebar`, `mixed $main` |


<a name="framework-ux-display-listview"></a>
#### `Framework\UX\Display\ListView`

列表

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Display/ListView.php`

**示例：**

```php
ListView::make()->item('项目1')->item('项目2')->item('项目3')
```

```php
ListView::make()->items($items)->bordered()->split(false)
```

```php
ListView::make()->header('标题')->footer('页脚')->loading()
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `item` | 添加单个列表项 | `mixed $content` |
| `items` | 批量设置列表项 | `array $items` |
| `bordered` | 启用边框 | `bool $bordered` = true |
| `split` | 设置是否显示项间分割线 | `bool $split` = true |
| `size` | 设置尺寸 | `string $size` |
| `sm` | 小尺寸 | — |
| `lg` | 大尺寸 | — |
| `header` | 设置列表头 | `string $header` |
| `footer` | 设置列表尾 | `string $footer` |
| `loading` | 设置加载状态 | `bool $loading` = true |


<a name="framework-ux-form-livericheditor"></a>
#### `Framework\UX\Form\LiveRichEditor`

实时富文本编辑器

**继承:** `Framework\Component\Live\LiveComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Form/LiveRichEditor.php`

**示例：**

```php
LiveRichEditor::make()->name('content')->label('内容')->toolbar(['bold', 'italic', 'link'])
```

```php
LiveRichEditor::make()->name('bio')->minimal()->placeholder('个人简介')
```

**属性：**

| 属性 | 类型 | 说明 |
|---|---|---|
| `$name` | `string` = '' |  |
| `$value` | `string` = '' |  |
| `$label` | `string` = '' |  |
| `$placeholder` | `string` = '' |  |
| `$toolbar` | `array` = ['bold', 'italic', 'underline', 'strike', '|', 'heading', 'quote', 'code', '|', 'list', 'link'] |  |
| `$minimal` | `bool` = false |  |
| `$required` | `bool` = false |  |
| `$disabled` | `bool` = false |  |
| `$rows` | `int` = 10 |  |
| `$outputFormat` | `string` = 'html' |  |
| `$extensions` | `array` = [] |  |

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `render` | 渲染编辑器 | — |
| `updateContent` | 更新内容（LiveAction） | `array $params` |
| `insertText` | 插入文本（LiveAction） | `array $params` |
| `clear` | 清空内容（LiveAction） | — |


<a name="framework-ux-feedback-loadingoverlay"></a>
#### `Framework\UX\Feedback\LoadingOverlay`

加载遮罩层组件

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Feedback/LoadingOverlay.php`

**示例：**

```php
LoadingOverlay::make()->id('page-loader')->fullscreen()
```

```php
LoadingOverlay::make()->id('table-loader')->inline()->text('加载中...')
```

```php
LoadingOverlay::make()->id('form-loader')->type('dots')->text('处理中')
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `text` | 设置加载提示文字 | `string $text` |
| `type` | 设置加载指示器类型 | `string $type` |
| `fullscreen` | 设置为全屏遮罩 | `bool $fullscreen` = true |
| `transparent` | 设置为透明背景 | `bool $transparent` = true |
| `size` | 设置加载指示器大小 | `string $size` |
| `sm` | 小尺寸指示器 | — |
| `lg` | 大尺寸指示器 | — |
| `inline` | 设置为内联模式（非全屏） | — |
| `open` | 设置显示状态 | `bool $open` = true |
| `close` | 关闭加载遮罩 | — |


<a name="framework-ux-richeditor-extensions-mentionextension"></a>
#### `Framework\UX\RichEditor\Extensions\MentionExtension`

**继承:** `Framework\UX\RichEditor\RichEditorExtension`  | **文件:** `php/src/UX/RichEditor/Extensions/MentionExtension.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `getName` |  | — |
| `setDataSource` | 设置数据源 | `array $data` |
| `setSearchCallback` | 设置搜索回调（自定义搜索逻辑） | `callable $callback` |
| `execute` | 执行 Mention 插入 | `string $content`, `array $params` = [] |
| `parse` |  | `string $content` |
| `renderPreview` |  | `string $content` |


<a name="framework-ux-menu-menu"></a>
#### `Framework\UX\Menu\Menu`

菜单

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Menu/Menu.php`

**示例：**

```php
Menu::make()->item('首页', '/')->item('关于', '/about')
```

```php
Menu::make()->item('产品', '/products')->subItem('产品1', '/p1')->subItem('产品2', '/p2')
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `direction` | 设置菜单方向 | `string $dir` |
| `horizontal` | 水平布局 | — |
| `vertical` | 垂直布局 | — |
| `item` | 添加菜单项 | `string $label`, `?string $href` = null, `?string $icon` = null, `bool $active` = false |
| `group` | 添加菜单分组（可包含子项） | `string $label`, `?string $icon` = null, `bool $open` = false, `?string $id` = null |
| `subitem` | 向最后一个分组添加子项（或作为独立项） | `string $label`, `?string $href` = null, `?string $icon` = null, `bool $active` = false |
| `divider` | 添加分隔线 | — |


<a name="framework-ux-dialog-modal"></a>
#### `Framework\UX\Dialog\Modal`

Modal 弹窗组件

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Dialog/Modal.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `title` | 设置弹窗标题 | `string $title` |
| `content` | 设置弹窗内容 | `mixed $content` |
| `size` | 设置弹窗尺寸 | `string $size` |
| `sm` | 小尺寸 | — |
| `lg` | 大尺寸 | — |
| `xl` | 超大尺寸 | — |
| `fullscreen` | 全屏尺寸 | — |
| `closeable` | 设置是否可关闭（显示关闭按钮） | `bool $closeable` = true |
| `backdrop` | 设置是否显示遮罩层 | `bool $backdrop` = true |
| `centered` | 设置是否居中显示 | `bool $centered` = true |
| `footer` | 设置底部内容 | `mixed $footer` |
| `open` | 设置打开状态 | `bool $open` = true |
| `close` | 关闭弹窗 | — |
| `ok` | 设置底部按钮（快捷方法：确定+取消） | `string $okText` = '确定', `string $okAction` = '', `string $okVariant` = 'primary', `string $cancelText` = '取消', `string $cancelVariant` = 'secondary' |
| `cancel` | 仅设置取消按钮（快捷方法） | `string $cancelText` = '取消', `string $cancelVariant` = 'secondary' |
| `trigger` | 生成触发按钮 | `string $label`, `string $variant` = 'primary' |


<a name="framework-ux-ui-navigate"></a>
#### `Framework\UX\UI\Navigate`

导航链接

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/UI/Navigate.php`

**示例：**

```php
Navigate::make()->url('/page')->label('前往页面')
```

```php
Navigate::make()->url('/section')->fragment('content')->child(Button::make()->label('跳转'))
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `href` | 设置导航地址 | `string $href` |
| `text` | 设置显示文本 | `string $text` |
| `fragment` | 设置导航片段（页面内锚点） | `string $name` |
| `target` | 设置打开目标 | `string $target` |
| `blank` | 在新标签页打开 | — |
| `replace` | 启用替换模式（不创建新历史记录） | `bool $replace` = true |
| `icon` | 设置图标 | `string $icon`, `string $position` = 'left' |
| `bi` | 使用 Bootstrap Icons | `string $name`, `string $position` = 'left' |
| `variant` | 设置颜色变体 | `string $variant` |
| `primary` | 主色变体 | — |
| `secondary` | 次色变体 | — |
| `danger` | 危险变体 | — |
| `success` | 成功变体 | — |
| `warning` | 警告变体 | — |
| `size` | 设置尺寸 | `string $size` |
| `sm` | 小尺寸 | — |
| `lg` | 大尺寸 | — |
| `disabled` | 设置禁用状态 | `bool $disabled` = true |
| `state` | 设置导航状态数据 | `string $key`, `mixed $value` |


<a name="framework-ux-navigation-pagination"></a>
#### `Framework\UX\Navigation\Pagination`

分页

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Navigation/Pagination.php`

**示例：**

```php
Pagination::make()->total(100)->current(2)->perPage(10)
```

```php
Pagination::make()->total($total)->perPageOptions([10, 20, 50])->showPerPage()
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `total` | 设置数据总量 | `int $total` |
| `current` | 设置当前页码 | `int $current` |
| `perPage` | 设置每页显示条数 | `int $perPage` |
| `baseUrl` | 设置分页基础 URL | `string $baseUrl` |
| `perPageOptions` | 设置每页条数可选值 | `array $options` |
| `perPageAction` | 设置每页条数变更的 LiveAction | `string $action` |
| `showPerPage` | 启用每页条数选择器并设置统计信息 | `int $total` = 0, `int $perPage` = 15, `int $current` = 1 |


<a name="framework-ux-richeditor-extensions-placeholderextension"></a>
#### `Framework\UX\RichEditor\Extensions\PlaceholderExtension`

**继承:** `Framework\UX\RichEditor\RichEditorExtension`  | **文件:** `php/src/UX/RichEditor/Extensions/PlaceholderExtension.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `getName` |  | — |
| `setPlaceholders` |  | `array $placeholders` |
| `addPlaceholder` | 添加占位符 | `string $key`, `string $label`, `mixed $defaultValue` = null |
| `execute` | 执行占位符插入 | `string $content`, `array $params` = [] |
| `parse` | 解析占位符标签为短代码 | `string $content` |
| `renderPreview` | 渲染预览内容（将短代码转为占位符标签） | `string $content` |
| `replaceInContent` | 在内容中替换占位符为实际值 | `string $content`, `array $values` |
| `getPlaceholders` | 获取所有占位符 | — |


<a name="framework-ux-overlay-popover"></a>
#### `Framework\UX\Overlay\Popover`

气泡卡片

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Overlay/Popover.php`

**示例：**

```php
Popover::make()->title('标题')->content('内容')->trigger('click')->placement('bottom')
```

```php
Popover::make()->content($view)->hover()->arrow(false)->maxWidth(300)
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `title` | 设置气泡标题 | `string $title` |
| `content` | 设置气泡内容 | `string $content` |
| `placement` | 设置气泡位置 | `string $placement` |
| `top` | 顶部气泡 | — |
| `bottom` | 底部气泡 | — |
| `left` | 左侧气泡 | — |
| `right` | 右侧气泡 | — |
| `trigger` | 设置触发方式 | `string $trigger` |
| `hover` | 悬停触发 | — |
| `click` | 点击触发 | — |
| `focus` | 聚焦触发 | — |
| `arrow` | 是否显示箭头 | `bool $arrow` = true |
| `maxWidth` | 设置最大宽度 | `int $width` |
| `open` | 设置打开状态 | `bool $open` = true |


<a name="framework-ux-feedback-progress"></a>
#### `Framework\UX\Feedback\Progress`

进度条

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Feedback/Progress.php`

**示例：**

```php
Progress::make()->value(75)->primary()
```

```php
Progress::make()->value(50)->success()->striped()->animated()
```

```php
Progress::make()->value(100)->showLabel()
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `value` | 设置进度值 | `int $value` |
| `max` | 设置最大值 | `int $max` |
| `variant` | 设置颜色变体 | `string $variant` |
| `primary` | 主色变体 | — |
| `success` | 成功变体 | — |
| `warning` | 警告变体 | — |
| `danger` | 危险变体 | — |
| `info` | 信息变体 | — |
| `showLabel` | 显示百分比标签 | `bool $show` = true |
| `striped` | 启用条纹样式 | `bool $striped` = true |
| `animated` | 启用条纹动画 | `bool $animated` = true |
| `size` | 设置尺寸 | `string $size` |
| `sm` | 小尺寸 | — |
| `lg` | 大尺寸 | — |


<a name="framework-ux-display-qrcode"></a>
#### `Framework\UX\Display\QRCode`

二维码

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Display/QRCode.php`

**示例：**

```php
QRCode::make()->value('https://example.com')->size(200)
```

```php
QRCode::make()->value('文本')->icon('/logo.png')->iconSize(40)
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `value` | 设置二维码内容 | `string $value` |
| `size` | 设置二维码尺寸 | `int $size` |
| `level` | 设置纠错级别 | `string $level` |
| `icon` | 设置中心图标 | `string $icon`, `int $size` = 32 |
| `color` | 设置前景色（二维码颜色） | `string $color` |
| `bgColor` | 设置背景色 | `string $bgColor` |


<a name="framework-ux-form-radio"></a>
#### `Framework\UX\Form\Radio`

单选框

**继承:** `Framework\UX\Form\FormField`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Form/Radio.php`

**示例：**

```php
Radio::make()->label('性别')->options(['male' => '男', 'female' => '女'])->model('gender')
```

```php
Radio::make()->label('支付方式')->options(['alipay' => '支付宝', 'wechat' => '微信'])->inline()
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `options` | 设置选项列表 | `array $options` |
| `inline` | 设置内联布局 | `bool $inline` = true |


<a name="framework-ux-form-radiogroup"></a>
#### `Framework\UX\Form\RadioGroup`

单选框组

**继承:** `Framework\UX\Form\FormField`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Form/RadioGroup.php`

**示例：**

```php
RadioGroup::make()->name('gender')->label('性别')->options(['male' => '男', 'female' => '女'])
```

```php
RadioGroup::make()->name('plan')->label('套餐')->options($plans)->inline()->liveModel('selectedPlan')
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `options` | 设置选项列表 | `array $options` |
| `inline` | 设置内联布局 | `bool $inline` = true |


<a name="framework-ux-form-rate"></a>
#### `Framework\UX\Form\Rate`

评分组件

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Form/Rate.php`

**示例：**

```php
Rate::make()->value(3)
```

```php
Rate::make()->allowHalf()->value(3.5)
```

```php
Rate::make()->readOnly()->value(4)
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `count` | 设置星星数量 | `int $count` |
| `value` | 设置默认评分值 | `float $value` |
| `allowHalf` | 允许半星选择 | `bool $allow` = true |
| `disabled` | 设置禁用状态 | `bool $disabled` = true |
| `readOnly` | 设置只读模式（显示但不可交互） | `bool $readOnly` = true |
| `character` | 设置自定义图标字符 | `string $character` |
| `action` | 设置 LiveAction（评分时触发） | `string $action` |
| `hoverAction` | 设置悬停触发 Action | `string $action` |


<a name="framework-ux-form-richeditor"></a>
#### `Framework\UX\Form\RichEditor`

富文本编辑器

**继承:** `Framework\UX\Form\FormField`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Form/RichEditor.php`

**示例：**

```php
RichEditor::make()->name('content')->label('内容')->toolbar(['bold', 'italic', 'link', 'image'])
```

```php
RichEditor::make()->name('article')->label('文章')->minimal()->placeholder('开始写作...')
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `liveModel` | 绑定 Live 属性 | `string $name` |
| `liveAction` | 设置 LiveAction 和触发事件 | `string $action`, `string $event` = 'change' |
| `rows` | 设置编辑器行数 | `int $rows` |
| `toolbar` | 设置工具栏项目 | `array $items` |
| `minimal` | 启用最小化模式（隐藏工具栏，无边框） | `bool $minimal` = true |
| `border` | 设置是否显示边框 | `bool $border` = true |
| `width` | 设置编辑器宽度 | `string $width` |
| `height` | 设置编辑器高度 | `string $height` |
| `placeholder` | 设置占位文本 | `string $placeholder` |
| `outputFormat` | 设置输出格式 | `string $format` |
| `extension` | 注册编辑器扩展 | `string $name`, `Framework\UX\RichEditor\RichEditorExtension $extension` |
| `parser` | 注册内容解析器 | `callable $parser`, `string $name` = 'default' |
| `parseContent` | 解析内容（应用扩展和解析器） | `string $content`, `string $parserName` = 'default' |
| `sanitize` | 清洗内容（移除危险标签和脚本） | `string $content` |


<a name="framework-ux-layout-row"></a>
#### `Framework\UX\Layout\Row`

行布局

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Layout/Row.php`

**示例：**

```php
Row::make()->justifyBetween()->alignCenter()->gap(4)->child($left)->child($right)
```

```php
Row::make()->justifyCenter()->gap(2)->wrap()->child($items)
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `justify` | 设置主轴对齐（水平方向） | `string $justify` |
| `justifyStart` | 左对齐 | — |
| `justifyCenter` | 居中对齐 | — |
| `justifyEnd` | 右对齐 | — |
| `justifyBetween` | 两端对齐（首尾） | — |
| `align` | 设置交叉轴对齐（垂直方向） | `string $align` |
| `alignStart` | 顶部对齐 | — |
| `alignCenter` | 居中对齐 | — |
| `alignEnd` | 底部对齐 | — |
| `gap` | 设置间距 | `int $gap` |
| `wrap` | 设置是否换行 | `bool $wrap` = true |
| `noWrap` | 不换行 | — |


<a name="framework-ux-form-searchinput"></a>
#### `Framework\UX\Form\SearchInput`

搜索输入框

**继承:** `Framework\UX\Form\FormField`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Form/SearchInput.php`

**示例：**

```php
SearchInput::make()->name('q')->label('搜索')->endpoint('/search/api')
```

```php
SearchInput::make()->name('keyword')->label('关键词')->options(['苹果', '香蕉', '橙子'])
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `endpoint` | 设置远程搜索端点 | `string $url` |
| `options` | 设置本地选项列表（用于自动完成） | `array $options` |


<a name="framework-ux-form-select"></a>
#### `Framework\UX\Form\Select`

下拉选择框

**继承:** `Framework\UX\Form\FormField`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Form/Select.php`

**示例：**

```php
Select::make()->label('城市')->options(['Beijing' => '北京', 'Shanghai' => '上海'])->model('city')
```

```php
Select::make()->label('多选')->options(['A' => '选项A', 'B' => '选项B'])->multiple()
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `options` | 设置选项列表 | `array $options` |
| `multiple` | 启用多选模式 | `bool $multiple` = true |
| `emptyOption` | 设置空选项/占位符文字 | `string $text` |
| `placeholder` | 设置占位符（等价于 emptyOption） | `string $placeholder` |


<a name="framework-ux-feedback-skeleton"></a>
#### `Framework\UX\Feedback\Skeleton`

骨架屏

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Feedback/Skeleton.php`

**示例：**

```php
Skeleton::make()->text()->count(3)
```

```php
Skeleton::make()->avatar()->width('100px')->height('100px')
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `type` | 设置骨架屏类型 | `string $type` |
| `text` | 文本类型 | — |
| `avatar` | 头像类型 | — |
| `rect` | 矩形类型 | — |
| `circle` | 圆形类型 | — |
| `count` | 设置重复数量 | `int $count` |
| `animated` | 设置是否带动画 | `bool $animated` = true |
| `width` | 设置宽度 | `string $width` |
| `height` | 设置高度 | `string $height` |


<a name="framework-ux-form-slider"></a>
#### `Framework\UX\Form\Slider`

滑块

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Form/Slider.php`

**示例：**

```php
Slider::make()->min(0)->max(100)->value(50)
```

```php
Slider::make()->range()->rangeValue(20, 80)->vertical()
```

```php
Slider::make()->value(75)->step(5)->format('%.0f%%')->showTooltip()
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `min` | 设置最小值 | `float $min` |
| `max` | 设置最大值 | `float $max` |
| `value` | 设置默认值 | `float $value` |
| `step` | 设置步长 | `float $step` |
| `disabled` | 设置禁用状态 | `bool $disabled` = true |
| `vertical` | 设置为垂直方向 | `bool $vertical` = true |
| `range` | 启用范围选择（双滑块） | `bool $range` = true |
| `rangeValue` | 设置范围值 | `float $start`, `float $end` |
| `showTooltip` | 显示提示框 | `bool $show` = true |
| `action` | 设置 LiveAction（滑块变化时触发） | `string $action` |
| `format` | 设置数值格式化 | `string $format` |


<a name="framework-ux-display-statcard"></a>
#### `Framework\UX\Display\StatCard`

统计卡片

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Display/StatCard.php`

**示例：**

```php
StatCard::make()->title('总收入')->value('¥12,345')->trendUp('12%')->icon('bi-currency-yen')
```

```php
StatCard::make()->title('用户数')->value('1,234')->description('较上月增长')->clickable()->clickAction('showDetails')
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `title` | 设置统计卡片标题 | `string $title` |
| `value` | 设置统计数值 | `string $value` |
| `description` | 设置描述文字 | `string $description` |
| `icon` | 设置图标 | `string $icon` |
| `trendUp` | 设置上升趋势 | `string $value` |
| `trendDown` | 设置下降趋势 | `string $value` |
| `variant` | 设置卡片变体 | `string $variant` |
| `clickAction` | 设置点击事件绑定的动作 | `string $action`, `string $event` = 'click' |
| `clickParams` | 设置点击参数 | `array $params` |
| `clickable` | 设置为可点击状态 | `bool $clickable` = true |


<a name="framework-ux-navigation-steps"></a>
#### `Framework\UX\Navigation\Steps`

步骤条

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Navigation/Steps.php`

**示例：**

```php
Steps::make()->item('步骤1')->item('步骤2')->item('步骤3')->current(2)
```

```php
Steps::make()->item('开始', '初始化')->item('处理', '运行中')->item('完成', '已结束')->vertical()
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `item` | 添加步骤项 | `string $title`, `?string $description` = null |
| `current` | 设置当前步骤（从 0 开始） | `int $current` |
| `vertical` | 设置为垂直布局 | — |


<a name="framework-ux-form-switchfield"></a>
#### `Framework\UX\Form\SwitchField`

开关

**继承:** `Framework\UX\Form\FormField`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Form/SwitchField.php`

**示例：**

```php
SwitchField::make()->name('notify')->label('接收通知')->checked()
```

```php
SwitchField::make()->name('agree')->label('同意条款')->onText('已同意')->offText('未同意')
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `checked` | 设置选中状态 | `bool $checked` = true |
| `onText` | 设置开启时显示的文字 | `string $text` |
| `offText` | 设置关闭时显示的文字 | `string $text` |


<a name="framework-ux-navigation-tabs"></a>
#### `Framework\UX\Navigation\Tabs`

标签页

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Navigation/Tabs.php`

**示例：**

```php
Tabs::make()->item('标签1', '内容1')->item('标签2', '内容2')
```

```php
Tabs::make()->item('首页', $view1)->item('关于', $view2)->pills()->justified()
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `item` | 添加标签页项 | `string $label`, `mixed $content`, `?string $id` = null, `bool $active` = false |
| `activeTab` | 设置当前激活的标签 | `string $id` |
| `variant` | 设置标签页变体 | `string $variant` |
| `liveModel` | 设置 Live 数据绑定 | `string $property` |
| `line` | 线型变体（底部横线） | — |
| `pills` | 胶囊型变体（圆角背景） | — |
| `justified` | 设置等宽分布 | `bool $justified` = true |


<a name="framework-ux-display-tag"></a>
#### `Framework\UX\Display\Tag`

标签

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Display/Tag.php`

**示例：**

```php
Tag::make()->text('标签')->primary()
```

```php
Tag::make()->text('成功')->success()->closable()
```

```php
Tag::make()->text('警告')->warning()->bordered()
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `text` | 设置标签文本 | `string $text` |
| `variant` | 设置颜色变体 | `string $variant` |
| `default` | 默认变体 | — |
| `primary` | 主色变体 | — |
| `success` | 成功变体 | — |
| `warning` | 警告变体 | — |
| `danger` | 危险变体 | — |
| `info` | 信息变体 | — |
| `size` | 设置尺寸 | `string $size` |
| `sm` | 小尺寸 | — |
| `lg` | 大尺寸 | — |
| `icon` | 设置图标 | `string $icon` |
| `closable` | 设置可关闭 | `bool $closable` = true |
| `bordered` | 设置带边框 | `bool $bordered` = true |
| `onClose` | 设置关闭按钮的 LiveAction | `string $action`, `string $event` = 'click' |


<a name="framework-ux-form-taginput"></a>
#### `Framework\UX\Form\TagInput`

标签输入框

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Form/TagInput.php`

**示例：**

```php
TagInput::make()->value(['PHP', 'Laravel', 'Vue'])
```

```php
TagInput::make()->placeholder('输入标签后按回车')->maxCount(5)->allowClear()
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `value` | 设置标签值列表 | `array $value` |
| `placeholder` | 设置占位文本 | `string $placeholder` |
| `disabled` | 设置禁用状态 | `bool $disabled` = true |
| `maxCount` | 设置最大标签数量（0 表示不限制） | `int $max` |
| `action` | 设置 LiveAction（标签变化时触发） | `string $action` |
| `allowClear` | 启用清除按钮 | `bool $allow` = true |


<a name="framework-ux-form-textarea"></a>
#### `Framework\UX\Form\Textarea`

多行文本框

**继承:** `Framework\UX\Form\FormField`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Form/Textarea.php`

**示例：**

```php
Textarea::make()->name('content')->label('内容')->rows(6)
```

```php
Textarea::make()->name('description')->label('描述')->placeholder('请输入描述')->liveModel('description')
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `rows` | 设置行数 | `int $rows` |


<a name="framework-ux-display-timeline"></a>
#### `Framework\UX\Display\Timeline`

时间线

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Display/Timeline.php`

**示例：**

```php
Timeline::make()->item('事件1', '2024-01-01')->item('事件2', '2024-02-01')
```

```php
Timeline::make()->item('事件', null, '✓', 'green')->reverse()
```

```php
Timeline::make()->item('左', '2024-01')->item('右', '2024-02')->mode('alternate')
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `item` | 添加时间线节点 | `string $content`, `?string $label` = null, `?string $dot` = null, `string $color` = 'blue' |
| `items` | 批量设置时间线节点 | `array $items` |
| `reverse` | 设置是否反向显示（从下到上） | `bool $reverse` = true |
| `mode` | 设置布局模式 | `string $mode` |
| `left` | 左侧布局 | — |
| `right` | 右侧布局 | — |
| `alternate` | 交错布局（左右交替） | — |


<a name="framework-ux-dialog-toast"></a>
#### `Framework\UX\Dialog\Toast`

消息提示

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Dialog/Toast.php`

**示例：**

```php
Toast::make()->message('操作成功')->success()
```

```php
Toast::make()->message('警告')->warning()->duration(5000)
```

```php
Toast::make()->message('错误')->error()->title('提示')->position('top-center')
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `message` | 设置提示消息 | `string $message` |
| `type` | 设置提示类型 | `string $type` |
| `success` | 成功类型 | — |
| `error` | 错误类型 | — |
| `warning` | 警告类型 | — |
| `info` | 信息类型 | — |
| `duration` | 设置自动关闭时长（毫秒） | `int $ms` |
| `closeable` | 设置是否可手动关闭 | `bool $closeable` = true |
| `title` | 设置标题 | `string $title` |
| `icon` | 设置图标 | `string $icon` |
| `position` | 设置显示位置 | `string $position` |
| `topRight` | 右上角 | — |
| `topLeft` | 左上角 | — |
| `bottomRight` | 右下角 | — |
| `bottomLeft` | 左下角 | — |
| `script` | 生成执行脚本 | — |
| `container` | 生成 Toast 容器 HTML | — |


<a name="framework-ux-overlay-tooltip"></a>
#### `Framework\UX\Overlay\Tooltip`

提示框

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Overlay/Tooltip.php`

**示例：**

```php
Tooltip::make()->content('这是一个提示')->trigger(Button::make()->label('悬停我'))
```

```php
Tooltip::make()->content($view)->position('top')->trigger('更多')
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `content` | 设置提示内容 | `string $content` |
| `placement` | 设置提示位置 | `string $placement` |
| `top` | 顶部提示 | — |
| `bottom` | 底部提示 | — |
| `left` | 左侧提示 | — |
| `right` | 右侧提示 | — |
| `trigger` | 设置触发方式 | `string $trigger` |
| `hover` | 悬停触发 | — |
| `click` | 点击触发 | — |
| `focus` | 聚焦触发 | — |
| `arrow` | 是否显示箭头 | `bool $arrow` = true |
| `delay` | 设置显示延迟（毫秒） | `int $delay` |
| `maxWidth` | 设置最大宽度 | `int $width` |


<a name="framework-ux-form-transfer"></a>
#### `Framework\UX\Form\Transfer`

穿梭框

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Form/Transfer.php`

**示例：**

```php
Transfer::make()->dataSource($users)->targetKeys([1, 2, 3])->titles('可选', '已选')
```

```php
Transfer::make()->dataSource($items)->targetKeys($selected)->showSearch()->searchPlaceholder('搜索...')
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `dataSource` | 设置数据源 | `array $data` |
| `targetKeys` | 设置目标项（已选中的项） | `array $keys` |
| `titles` | 设置左右面板标题 | `string $left`, `string $right` |
| `disabled` | 设置禁用状态 | `bool $disabled` = true |
| `showSearch` | 启用搜索功能 | `bool $show` = true |
| `action` | 设置 LiveAction（穿梭时触发） | `string $action` |
| `searchPlaceholder` | 设置搜索占位文本 | `string $placeholder` |


<a name="framework-ux-form-treeselect"></a>
#### `Framework\UX\Form\TreeSelect`

树形选择器

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Form/TreeSelect.php`

**示例：**

```php
TreeSelect::make()->treeData($deptTree)->placeholder('选择部门')
```

```php
TreeSelect::make()->treeData($roles)->multiple()->showSearch()->allowClear()
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `treeData` | 设置树形数据 | `array $data` |
| `value` | 设置选中值 | `string $value` |
| `placeholder` | 设置占位文本 | `string $placeholder` |
| `multiple` | 启用多选模式 | `bool $multiple` = true |
| `disabled` | 设置禁用状态 | `bool $disabled` = true |
| `allowClear` | 启用清除按钮 | `bool $allow` = true |
| `showSearch` | 启用搜索功能 | `bool $show` = true |
| `action` | 设置 LiveAction（选择时触发） | `string $action` |
| `emptyText` | 设置空状态提示文字 | `string $text` |


<a name="framework-ux-uxcomponent"></a>
#### `Framework\UX\UXComponent`

UX 组件基类

**实现:** `Stringable`  | **abstract**  | **文件:** `php/src/UX/UXComponent.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `make` | 静态工厂方法，创建组件实例 | — |
| `id` | 设置组件 ID | `string $id` |
| `class` | 添加 CSS 类名 | `string $class` |
| `style` | 设置内联样式 | `string $style` |
| `attr` | 设置 HTML 属性 | `string $name`, `string $value` |
| `model` | 设置 data-model 绑定 | `string $name` |
| `liveModel` | 设置 Live 双向绑定，通过桥接层自动同步 UX 组件值到 LiveComponent 属性 | `string $property` |
| `data` | 设置 data-* 属性 | `string $key`, `string $value` |
| `dataLiveSse` | 订阅 SSE 频道（data-live-sse） | `string $channels` |
| `child` | 添加子元素 | `mixed $child` |
| `children` | 批量添加子元素 | `mixed $children` |
| `liveAction` | 设置 Live Action，点击时触发后端方法 | `string $action`, `string $event` = 'click' |
| `stream` | 标记此动作为流式响应 | — |
| `on` | 绑定事件监听器 | `string $event`, `string $handler` |
| `onOpen` | 绑定 open 事件 | `string $handler` |
| `onClose` | 绑定 close 事件 | `string $handler` |
| `dispatch` | 派发自定义事件，绑定到 click | `string $event`, `?string $detail` = null |
| `render` | 渲染为 HTML 字符串 @ux-internal | — |


<a name="framework-ux-display-watermark"></a>
#### `Framework\UX\Display\Watermark`

水印

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Display/Watermark.php`

**示例：**

```php
Watermark::make()->content('机密')->fontSize(20)->rotate(-45)
```

```php
Watermark::make()->content('预览')->gap(150, 150)->zIndex(1)
```

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `content` | 设置水印文字内容 | `string $content` |
| `fontSize` | 设置水印字体大小 | `int $size` |
| `fontColor` | 设置水印字体颜色 | `string $color` |
| `rotate` | 设置水印旋转角度 | `int $rotate` |
| `gap` | 设置水印间距 | `int $x`, `int $y` |
| `zIndex` | 设置水印层级 | `int $zIndex` |


