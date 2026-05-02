# UX 组件库 — API 参考

> 由 DocGen 自动生成于 2026-05-02 05:37:00

## 目录

**其他**
- [`Accordion`](#framework-ux-ui-accordion)
- [`Alert`](#framework-ux-feedback-alert)
- [`Avatar`](#framework-ux-display-avatar)
- [`Badge`](#framework-ux-display-badge)
- [`BatchActionsMenu`](#framework-ux-data-batchactionsmenu)
- [`Breadcrumb`](#framework-ux-navigation-breadcrumb)
- [`Button`](#framework-ux-ui-button)
- [`Calendar`](#framework-ux-data-calendar) — Calendar 日历组件
- [`Card`](#framework-ux-display-card)
- [`Carousel`](#framework-ux-media-carousel)
- [`Chart`](#framework-ux-chart-chart)
- [`Checkbox`](#framework-ux-form-checkbox)
- [`Collapse`](#framework-ux-display-collapse)
- [`ColorPicker`](#framework-ux-form-colorpicker)
- [`DataCard`](#framework-ux-data-datacard)
- [`DataGrid`](#framework-ux-data-datagrid)
- [`DataList`](#framework-ux-data-datalist)
- [`DataTable`](#framework-ux-data-datatable)
- [`DataTableColumn`](#framework-ux-data-datatablecolumn)
- [`DataTree`](#framework-ux-data-datatree)
- [`DatePicker`](#framework-ux-form-datepicker) — 日期选择器
- [`DateRangePicker`](#framework-ux-form-daterangepicker)
- [`DescriptionList`](#framework-ux-data-descriptionlist)
- [`Divider`](#framework-ux-display-divider)
- [`DocumentParser`](#framework-ux-richeditor-documentparser)
- [`Drawer`](#framework-ux-dialog-drawer)
- [`Dropdown`](#framework-ux-menu-dropdown)
- [`EmojiExtension`](#framework-ux-richeditor-extensions-emojiextension)
- [`EmptyState`](#framework-ux-feedback-emptystate)
- [`ExtensionRegistry`](#framework-ux-richeditor-extensionregistry)
- [`FileUpload`](#framework-ux-form-fileupload)
- [`FormBuilder`](#framework-ux-form-formbuilder)
- [`FormField`](#framework-ux-form-formfield)
- [`Grid`](#framework-ux-layout-grid)
- [`Image`](#framework-ux-media-image)
- [`Input`](#framework-ux-form-input)
- [`Layout`](#framework-ux-layout-layout)
- [`ListView`](#framework-ux-display-listview)
- [`LiveRichEditor`](#framework-ux-form-livericheditor)
- [`MentionExtension`](#framework-ux-richeditor-extensions-mentionextension)
- [`Menu`](#framework-ux-menu-menu)
- [`Modal`](#framework-ux-dialog-modal) — Modal 弹窗组件
- [`Navigate`](#framework-ux-ui-navigate)
- [`Pagination`](#framework-ux-navigation-pagination)
- [`PlaceholderExtension`](#framework-ux-richeditor-extensions-placeholderextension)
- [`Popover`](#framework-ux-overlay-popover)
- [`Progress`](#framework-ux-feedback-progress)
- [`QRCode`](#framework-ux-display-qrcode)
- [`Radio`](#framework-ux-form-radio)
- [`RadioGroup`](#framework-ux-form-radiogroup)
- [`Rate`](#framework-ux-form-rate) — 评分组件
- [`RichEditor`](#framework-ux-form-richeditor)
- [`RichEditorExtension`](#framework-ux-richeditor-richeditorextension)
- [`Row`](#framework-ux-layout-row)
- [`SearchInput`](#framework-ux-form-searchinput)
- [`Select`](#framework-ux-form-select)
- [`Skeleton`](#framework-ux-feedback-skeleton)
- [`Slider`](#framework-ux-form-slider)
- [`StatCard`](#framework-ux-display-statcard)
- [`Steps`](#framework-ux-navigation-steps)
- [`SwitchField`](#framework-ux-form-switchfield)
- [`Tabs`](#framework-ux-navigation-tabs)
- [`Tag`](#framework-ux-display-tag) — 标签
- [`TagInput`](#framework-ux-form-taginput)
- [`Textarea`](#framework-ux-form-textarea)
- [`Timeline`](#framework-ux-display-timeline)
- [`Toast`](#framework-ux-dialog-toast)
- [`Tooltip`](#framework-ux-overlay-tooltip)
- [`Transfer`](#framework-ux-form-transfer)
- [`TreeSelect`](#framework-ux-form-treeselect)
- [`UXComponent`](#framework-ux-uxcomponent) — UX 组件基类
- [`Watermark`](#framework-ux-display-watermark)

---

### 其他

<a name="framework-ux-ui-accordion"></a>
#### `Framework\UX\UI\Accordion`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/UI/Accordion.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `item` |  | `mixed $title`, `mixed $content`, `?string $id` = null, `bool $open` = false |
| `multiple` |  | `bool $multiple` = true |
| `variant` |  | `string $variant` |
| `dark` |  | `bool $dark` = true |


<a name="framework-ux-feedback-alert"></a>
#### `Framework\UX\Feedback\Alert`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Feedback/Alert.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `message` |  | `string $message` |
| `type` |  | `string $type` |
| `success` |  | — |
| `error` |  | — |
| `warning` |  | — |
| `info` |  | — |
| `dismissible` |  | `bool $dismissible` = true |
| `title` |  | `string $title` |
| `icon` |  | `string $icon` |


<a name="framework-ux-display-avatar"></a>
#### `Framework\UX\Display\Avatar`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Display/Avatar.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `src` |  | `string $src` |
| `name` |  | `string $name` |
| `size` |  | `string $size` |
| `shape` |  | `string $shape` |
| `circle` |  | — |
| `rounded` |  | — |
| `status` |  | `string $status` |


<a name="framework-ux-display-badge"></a>
#### `Framework\UX\Display\Badge`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Display/Badge.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `make` |  | `mixed $text` = '' |
| `variant` |  | `string $variant` |
| `default` |  | — |
| `primary` |  | — |
| `success` |  | — |
| `warning` |  | — |
| `danger` |  | — |
| `info` |  | — |
| `size` |  | `string $size` |
| `sm` |  | — |
| `md` |  | — |
| `lg` |  | — |
| `pill` |  | `bool $pill` = true |
| `dot` |  | `bool $dot` = true |
| `text` |  | `string $text` |


<a name="framework-ux-data-batchactionsmenu"></a>
#### `Framework\UX\Data\BatchActionsMenu`

**文件:** `php/src/UX/Data/BatchActionsMenu.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `emptyText` |  | `string $text` |
| `selectCountText` |  | `string $text` |
| `action` |  | `string $label`, `string $action`, `string $variant` = 'default', `?string $icon` = null, `?string $confirm` = null |
| `actions` |  | `array $actions` |
| `liveAction` |  | `string $action`, `string $event` = 'click' |
| `selectedKeys` |  | `array $keys` |
| `visible` |  | `bool $visible` = true |
| `render` |  | — |


<a name="framework-ux-navigation-breadcrumb"></a>
#### `Framework\UX\Navigation\Breadcrumb`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Navigation/Breadcrumb.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `item` |  | `string $label`, `?string $link` = null |
| `separator` |  | `string $separator` |


<a name="framework-ux-ui-button"></a>
#### `Framework\UX\UI\Button`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/UI/Button.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `label` |  | `string $label` |
| `type` |  | `string $type` |
| `submit` |  | — |
| `variant` |  | `string $variant` |
| `primary` |  | — |
| `secondary` |  | — |
| `danger` |  | — |
| `success` |  | — |
| `warning` |  | — |
| `size` |  | `string $size` |
| `sm` |  | — |
| `lg` |  | — |
| `icon` | 设置图标 | `string $icon`, `string $position` = 'left', `string $family` = 'bi' |
| `bi` | 使用 Bootstrap Icons | `string $name`, `string $position` = 'left' |
| `loading` |  | `bool $loading` = true |
| `disabled` |  | `bool $disabled` = true |
| `outline` |  | `bool $outline` = true |
| `block` |  | `bool $block` = true |
| `href` | 将按钮设置为链接模式 | `string $href` |
| `navigate` | 启用无刷新导航（data-navigate） | `string $url`, `?string $fragment` = null |
| `openModal` |  | `string $modalId` |
| `closeModal` |  | `?string $modalId` = null |
| `showToast` |  | `string $message`, `string $type` = 'success' |


<a name="framework-ux-data-calendar"></a>
#### `Framework\UX\Data\Calendar`

Calendar 日历组件

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Data/Calendar.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `value` |  | `string $value` |
| `mode` |  | `string $mode` |
| `month` |  | — |
| `year` |  | — |
| `fullscreen` |  | `bool $fullscreen` = true |
| `disabled` |  | `bool $disabled` = true |
| `action` |  | `string $action` |
| `validRange` |  | `string $start`, `string $end` |


<a name="framework-ux-display-card"></a>
#### `Framework\UX\Display\Card`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Display/Card.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `title` |  | `string $title` |
| `subtitle` |  | `string $subtitle` |
| `header` |  | `mixed $header` |
| `footer` |  | `mixed $footer` |
| `image` |  | `string $src`, `string $position` = 'top' |
| `variant` |  | `string $variant` |
| `bordered` |  | — |
| `shadow` |  | — |
| `flat` |  | — |


<a name="framework-ux-media-carousel"></a>
#### `Framework\UX\Media\Carousel`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Media/Carousel.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `item` |  | `string $content`, `?string $title` = null |
| `items` |  | `array $items` |
| `autoplay` |  | `bool $autoplay` = true, `int $interval` = 3000 |
| `dots` |  | `bool $dots` = true |
| `arrows` |  | `bool $arrows` = true |
| `effect` |  | `string $effect` |
| `fade` |  | — |
| `loop` |  | `bool $loop` = true |
| `action` |  | `string $action` |


<a name="framework-ux-chart-chart"></a>
#### `Framework\UX\Chart\Chart`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Chart/Chart.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `type` |  | `string $type` |
| `labels` |  | `array $labels` |
| `dataset` |  | `string $label`, `array $data`, `array $options` = [] |
| `chartData` |  | `array $chartData` |
| `options` |  | `array $options` |
| `title` |  | `string $title` |
| `description` |  | `string $description` |
| `height` |  | `int $height` |
| `showLegend` |  | `bool $show` = true |
| `showGrid` |  | `bool $show` = true |
| `animation` |  | `string $animation` |


<a name="framework-ux-form-checkbox"></a>
#### `Framework\UX\Form\Checkbox`

**继承:** `Framework\UX\Form\FormField`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Form/Checkbox.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `checked` |  | `bool $checked` = true |


<a name="framework-ux-display-collapse"></a>
#### `Framework\UX\Display\Collapse`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Display/Collapse.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `title` |  | `string $title` |
| `open` |  | `bool $open` = true |
| `disabled` |  | `bool $disabled` = true |
| `icon` |  | `string $icon` |
| `action` |  | `string $action` |


<a name="framework-ux-form-colorpicker"></a>
#### `Framework\UX\Form\ColorPicker`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Form/ColorPicker.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `value` |  | `string $value` |
| `allowClear` |  | `bool $allow` = true |
| `disabled` |  | `bool $disabled` = true |
| `showText` |  | `bool $show` = true |
| `action` |  | `string $action` |
| `presets` |  | `array $presets` |


<a name="framework-ux-data-datacard"></a>
#### `Framework\UX\Data\DataCard`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Data/DataCard.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `field` |  | `string $label`, `string $dataKey`, `?Closure $render` = null, `array $options` = [] |
| `fields` |  | `array $fields` |
| `dataSource` |  | `array $data` |
| `item` |  | `array $data` |
| `variant` |  | `string $variant` |
| `title` |  | `string $title` |
| `subtitle` |  | `string $subtitle` |
| `avatar` |  | `mixed $avatar` |
| `actions` |  | `mixed $actions` |
| `cover` |  | `mixed $cover` |
| `bordered` |  | `bool $bordered` = true |
| `fragment` |  | `string $name` |


<a name="framework-ux-data-datagrid"></a>
#### `Framework\UX\Data\DataGrid`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Data/DataGrid.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `dataSource` |  | `array $data` |
| `rows` |  | `array $data` |
| `renderItem` |  | `Closure $callback` |
| `cols` |  | `int $cols` |
| `gap` |  | `int $gap` |
| `variant` |  | `string $variant` |
| `emptyText` |  | `string $text` |
| `header` |  | `mixed $header` |
| `footer` |  | `mixed $footer` |
| `title` |  | `string $title` |
| `pagination` |  | `int $total`, `int $current` = 1, `int $perPage` = 15, `string $baseUrl` = '' |
| `fragment` |  | `string $name` |
| `itemAction` |  | `string $action`, `string $event` = 'click' |
| `pageAction` |  | `string $action` |


<a name="framework-ux-data-datalist"></a>
#### `Framework\UX\Data\DataList`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Data/DataList.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `dataSource` |  | `array $data` |
| `rows` |  | `array $data` |
| `renderItem` |  | `Closure $callback` |
| `variant` |  | `string $variant` |
| `size` |  | `string $size` |
| `sm` |  | — |
| `lg` |  | — |
| `bordered` |  | `bool $bordered` = true |
| `split` |  | `bool $split` = true |
| `emptyText` |  | `string $text` |
| `header` |  | `mixed $header` |
| `footer` |  | `mixed $footer` |
| `title` |  | `string $title` |
| `pagination` |  | `int $total`, `int $current` = 1, `int $perPage` = 15, `string $baseUrl` = '' |
| `fragment` |  | `string $name` |
| `itemAction` |  | `string $action`, `string $event` = 'click' |
| `pageAction` |  | `string $action` |


<a name="framework-ux-data-datatable"></a>
#### `Framework\UX\Data\DataTable`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Data/DataTable.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `column` |  | `string $dataKey`, `string $title`, `?Closure $render` = null, `array $options` = [] |
| `addColumn` | Add a column using DataTableColumn object (chainable) | `Framework\UX\Data\DataTableColumn $column` |
| `getSearchableColumns` | Get searchable columns | — |
| `columns` |  | `array $columns` |
| `dataSource` |  | `array $data` |
| `rows` |  | `array $data` |
| `rowKey` |  | `string $key` |
| `size` |  | `string $size` |
| `sm` |  | — |
| `lg` |  | — |
| `striped` |  | `bool $striped` = true |
| `bordered` |  | `bool $bordered` = true |
| `hoverable` |  | `bool $hoverable` = true |
| `selectable` |  | `bool $selectable` = true |
| `emptyText` |  | `string $text` |
| `header` |  | `mixed $header` |
| `footer` |  | `mixed $footer` |
| `title` |  | `string $title` |
| `pagination` |  | `int $total`, `int $current` = 1, `int $perPage` = 15, `string $baseUrl` = '' |
| `paginationComponent` |  | `Framework\UX\UI\Pagination $pagination` |
| `rowAttr` |  | `string $key`, `string $value` |
| `rowCallback` |  | `Closure $callback` |
| `sortField` |  | `?string $field` |
| `sortDirection` |  | `string $direction` |
| `fragment` |  | `string $name` |
| `sortAction` |  | `string $action` |
| `pageAction` |  | `string $action` |
| `selectAction` |  | `string $action` |
| `registerAction` |  | `string $name`, `string $action`, `string $event` = 'click', `array $config` = [] |
| `actions` |  | `array $actions` |
| `searchable` |  | `bool $searchable` = true |
| `searchAction` |  | `string $action` |
| `searchValue` |  | `?string $value` |
| `searchPlaceholder` |  | `string $placeholder` |
| `batchActions` |  | `array $actions` |
| `batchAction` |  | `string $action` |
| `perPageOptions` |  | `array $options` |
| `perPageAction` |  | `string $action` |
| `showPerPage` |  | `bool $show` = true, `int $total` = 0, `int $perPage` = 15, `int $page` = 1 |
| `tooltip` |  | `?Closure $callback` |
| `rowActions` | 注册行操作组件 闭包接收 ($row, $rowKey, $rowIndex) 返回组件数组 | `Closure $callback` |


<a name="framework-ux-data-datatablecolumn"></a>
#### `Framework\UX\Data\DataTableColumn`

**文件:** `php/src/UX/Data/DataTableColumn.php`

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
| `width` |  | `?string $width` |
| `align` |  | `?string $align` |
| `alignCenter` |  | — |
| `alignRight` |  | — |
| `alignLeft` |  | — |
| `sortable` |  | `bool $sortable` = true |
| `fixed` |  | `?string $position` |
| `fixedLeft` |  | — |
| `fixedRight` |  | — |
| `visible` |  | `bool $visible` = true |
| `hidden` |  | — |
| `tooltip` |  | `?string $tooltip` |
| `render` |  | `Closure $callback` |
| `searchable` |  | `bool $searchable` = true, `string $type` = 'like', `?array $options` = null |
| `searchEqual` |  | — |
| `searchLike` |  | — |
| `searchIn` |  | `?array $options` = null |
| `toArray` |  | — |


<a name="framework-ux-data-datatree"></a>
#### `Framework\UX\Data\DataTree`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Data/DataTree.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `treeData` |  | `array $data` |
| `renderTitle` |  | `Closure $callback` |
| `variant` |  | `string $variant` |
| `showLine` |  | `bool $showLine` = true |
| `showIcon` |  | `bool $showIcon` = true |
| `selectable` |  | `bool $selectable` = true |
| `checkable` |  | `bool $checkable` = true |
| `defaultExpandAll` |  | `bool $expand` = true |
| `defaultExpandedKeys` |  | `array $keys` |
| `emptyText` |  | `string $text` |
| `title` |  | `string $title` |
| `header` |  | `mixed $header` |
| `fragment` |  | `string $name` |
| `toggleAction` |  | `string $action` |
| `selectAction` |  | `string $action` |
| `checkAction` |  | `string $action` |


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

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Form/DateRangePicker.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `startValue` |  | `string $value` |
| `endValue` |  | `string $value` |
| `value` |  | `string $start`, `string $end` |
| `placeholder` |  | `string $placeholder` |
| `format` |  | `string $format` |
| `disabled` |  | `bool $disabled` = true |
| `allowClear` |  | `bool $allow` = true |
| `showToday` |  | `bool $show` = true |
| `minDate` |  | `string $date` |
| `maxDate` |  | `string $date` |
| `action` |  | `string $action` |
| `separator` |  | `string $separator` |
| `showTime` |  | `bool $show` = true |


<a name="framework-ux-data-descriptionlist"></a>
#### `Framework\UX\Data\DescriptionList`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Data/DescriptionList.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `item` |  | `string $label`, `mixed $value`, `?Closure $render` = null |
| `items` |  | `array $items` |
| `columns` |  | `int $columns` |
| `variant` |  | `string $variant` |
| `size` |  | `string $size` |
| `sm` |  | — |
| `lg` |  | — |
| `bordered` |  | `bool $bordered` = true |
| `title` |  | `string $title` |
| `extra` |  | `mixed $extra` |
| `labelAlign` |  | `string $align` |
| `fragment` |  | `string $name` |


<a name="framework-ux-display-divider"></a>
#### `Framework\UX\Display\Divider`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Display/Divider.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `text` |  | `string $text` |
| `orientation` |  | `string $orientation` |
| `orientationLeft` |  | — |
| `orientationRight` |  | — |
| `orientationCenter` |  | — |
| `vertical` |  | — |
| `horizontal` |  | — |
| `dashed` |  | `bool $dashed` = true |
| `variant` |  | `string $variant` |
| `primary` |  | — |
| `success` |  | — |
| `warning` |  | — |
| `danger` |  | — |


<a name="framework-ux-richeditor-documentparser"></a>
#### `Framework\UX\RichEditor\DocumentParser`

**文件:** `php/src/UX/RichEditor/DocumentParser.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `htmlToText` |  | `string $html` |
| `htmlToMarkdown` |  | `string $html` |
| `markdownToHtml` |  | `string $markdown` |
| `extractPlainText` |  | `string $html`, `int $maxLength` = 0 |
| `truncateHtml` |  | `string $html`, `int $maxLength`, `string $suffix` = '...' |
| `sanitize` |  | `string $html`, `array\|string\|null $allowedTags` = null |
| `wordCount` |  | `string $content` |
| `characterCount` |  | `string $content`, `bool $includeSpaces` = true |
| `registerProcessor` |  | `string $name`, `callable $processor` |
| `registerFilter` |  | `string $name`, `callable $filter` |
| `process` |  | `string $content`, `array $processorNames` = [] |
| `filter` |  | `string $content`, `array $filterNames` = [] |


<a name="framework-ux-dialog-drawer"></a>
#### `Framework\UX\Dialog\Drawer`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Dialog/Drawer.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `title` |  | `string $title` |
| `child` |  | `mixed $child` |
| `position` |  | `string $position` |
| `left` |  | — |
| `right` |  | — |
| `top` |  | — |
| `bottom` |  | — |
| `size` |  | `string $size` |
| `sm` |  | — |
| `md` |  | — |
| `lg` |  | — |
| `xl` |  | — |
| `full` |  | — |
| `open` |  | `bool $open` = true |
| `trigger` |  | `string $label`, `string $variant` = 'primary' |


<a name="framework-ux-menu-dropdown"></a>
#### `Framework\UX\Menu\Dropdown`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Menu/Dropdown.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `label` |  | `string $label` |
| `item` |  | `string $label`, `?string $url` = '#', `?string $icon` = null, `?string $action` = null, `array $params` = [] |
| `noborder` |  | `bool $noborder` = true |
| `element` | 传入自定义 Element 作为菜单项（最灵活） | `mixed $content` |
| `divider` |  | — |
| `position` |  | `string $position` |
| `hover` |  | `bool $hover` = true |
| `customTrigger` | 自定义 trigger 元素（替代默认的文字按钮） | `mixed $trigger` |


<a name="framework-ux-richeditor-extensions-emojiextension"></a>
#### `Framework\UX\RichEditor\Extensions\EmojiExtension`

**继承:** `Framework\UX\RichEditor\RichEditorExtension`  | **文件:** `php/src/UX/RichEditor/Extensions/EmojiExtension.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `getName` |  | — |
| `setEmojiMap` |  | `array $map` |
| `execute` |  | `string $content`, `array $params` = [] |
| `parse` |  | `string $content` |
| `renderPreview` |  | `string $content` |
| `getAvailableEmojis` |  | — |
| `getToolbarButton` |  | `string $editorId` |


<a name="framework-ux-feedback-emptystate"></a>
#### `Framework\UX\Feedback\EmptyState`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Feedback/EmptyState.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `description` |  | `string $description` |
| `image` |  | `string $image` |
| `imageStyle` |  | `string $style` |
| `extra` |  | `mixed $extra` |
| `size` |  | `string $size` |
| `sm` |  | — |
| `lg` |  | — |


<a name="framework-ux-richeditor-extensionregistry"></a>
#### `Framework\UX\RichEditor\ExtensionRegistry`

**文件:** `php/src/UX/RichEditor/ExtensionRegistry.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `register` |  | `string $name`, `Framework\UX\RichEditor\RichEditorExtension $extension` |
| `get` |  | `string $name` |
| `has` |  | `string $name` |
| `all` |  | — |
| `remove` |  | `string $name` |
| `registerParser` |  | `string $name`, `callable $parser` |
| `getParser` |  | `string $name` |
| `registerFormatter` |  | `string $format`, `callable $formatter` |
| `getFormatter` |  | `string $format` |
| `parseWith` |  | `string $content`, `string $parserName` |
| `formatAs` |  | `string $content`, `string $format` |
| `clear` |  | — |


<a name="framework-ux-form-fileupload"></a>
#### `Framework\UX\Form\FileUpload`

**继承:** `Framework\UX\Form\FormField`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Form/FileUpload.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `multiple` |  | `bool $multiple` = true |
| `accept` |  | `string $accept` |
| `images` |  | — |
| `documents` |  | — |
| `maxSize` |  | `int $kb` |


<a name="framework-ux-form-formbuilder"></a>
#### `Framework\UX\Form\FormBuilder`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Form/FormBuilder.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `method` |  | `string $method` |
| `get` |  | — |
| `post` |  | — |
| `put` |  | — |
| `delete` |  | — |
| `action` |  | `string $action` |
| `multipart` |  | `bool $multipart` = true |
| `submitLabel` |  | `string $label` |
| `text` |  | `string $name`, `string $label`, `array $options` = [] |
| `email` |  | `string $name`, `string $label`, `array $options` = [] |
| `password` |  | `string $name`, `string $label`, `array $options` = [] |
| `number` |  | `string $name`, `string $label`, `array $options` = [] |
| `textarea` |  | `string $name`, `string $label`, `array $options` = [] |
| `richEditor` |  | `string $name`, `string $label`, `array $options` = [] |
| `select` |  | `string $name`, `string $label`, `array $options` = [], `array $selectOptions` = [] |
| `checkbox` |  | `string $name`, `string $label`, `array $options` = [] |
| `radio` |  | `string $name`, `string $label`, `array $choices`, `array $options` = [] |
| `file` |  | `string $name`, `string $label`, `array $options` = [] |
| `hidden` |  | `string $name`, `string $value` |
| `liveBind` |  | `string $field`, `string $property` |
| `fill` |  | `array $data` |


<a name="framework-ux-form-formfield"></a>
#### `Framework\UX\Form\FormField`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **abstract**  | **文件:** `php/src/UX/Form/FormField.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `name` |  | `string $name` |
| `label` |  | `string $label` |
| `required` |  | `bool $required` = true |
| `value` |  | `mixed $value` |
| `placeholder` |  | `string $placeholder` |
| `help` |  | `string $help` |
| `disabled` |  | `bool $disabled` = true |
| `readonly` |  | `bool $readonly` = true |
| `autocomplete` |  | `string $autocomplete` |
| `rules` |  | `array $rules` |
| `liveModel` |  | `string $property` |
| `getName` |  | — |


<a name="framework-ux-layout-grid"></a>
#### `Framework\UX\Layout\Grid`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Layout/Grid.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `cols` |  | `int $cols` |
| `gap` |  | `int $gap` |
| `align` |  | `string $align` |
| `alignStart` |  | — |
| `alignCenter` |  | — |
| `alignEnd` |  | — |


<a name="framework-ux-media-image"></a>
#### `Framework\UX\Media\Image`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Media/Image.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `src` |  | `string $src` |
| `alt` |  | `string $alt` |
| `width` |  | `int $width` |
| `height` |  | `int $height` |
| `preview` |  | `bool $preview` = true |
| `lazy` |  | `bool $lazy` = true |
| `fallback` |  | `string $fallback` |
| `fit` |  | `string $fit` |
| `contain` |  | — |
| `cover` |  | — |
| `scaleDown` |  | — |


<a name="framework-ux-form-input"></a>
#### `Framework\UX\Form\Input`

**继承:** `Framework\UX\Form\FormField`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Form/Input.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `type` |  | `string $type` |
| `email` |  | — |
| `password` |  | — |
| `number` |  | — |
| `date` |  | — |
| `datetime` |  | — |
| `time` |  | — |
| `url` |  | — |
| `tel` |  | — |
| `search` |  | — |
| `color` |  | — |
| `range` |  | — |


<a name="framework-ux-layout-layout"></a>
#### `Framework\UX\Layout\Layout`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Layout/Layout.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `sidebar` |  | `bool $left` = true |
| `sidebarLeft` |  | — |
| `sidebarRight` |  | — |
| `sidebarWidth` |  | `int $width` |
| `header` |  | `bool $fixed` = false |
| `footer` |  | `bool $fixed` = false |
| `renderHeader` |  | `mixed $content` |
| `renderSidebar` |  | `mixed $content` |
| `renderMain` |  | `mixed $content` |
| `renderFooter` |  | `mixed $content` |
| `renderBody` |  | `mixed $sidebar`, `mixed $main` |


<a name="framework-ux-display-listview"></a>
#### `Framework\UX\Display\ListView`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Display/ListView.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `item` |  | `mixed $content` |
| `items` |  | `array $items` |
| `bordered` |  | `bool $bordered` = true |
| `split` |  | `bool $split` = true |
| `size` |  | `string $size` |
| `sm` |  | — |
| `lg` |  | — |
| `header` |  | `string $header` |
| `footer` |  | `string $footer` |
| `loading` |  | `bool $loading` = true |


<a name="framework-ux-form-livericheditor"></a>
#### `Framework\UX\Form\LiveRichEditor`

**继承:** `Framework\Component\Live\LiveComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Form/LiveRichEditor.php`

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
| `render` |  | — |
| `updateContent` |  | `array $params` |
| `insertText` |  | `array $params` |
| `clear` |  | — |


<a name="framework-ux-richeditor-extensions-mentionextension"></a>
#### `Framework\UX\RichEditor\Extensions\MentionExtension`

**继承:** `Framework\UX\RichEditor\RichEditorExtension`  | **文件:** `php/src/UX/RichEditor/Extensions/MentionExtension.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `getName` |  | — |
| `setDataSource` |  | `array $data` |
| `setSearchCallback` |  | `callable $callback` |
| `execute` |  | `string $content`, `array $params` = [] |
| `parse` |  | `string $content` |
| `renderPreview` |  | `string $content` |


<a name="framework-ux-menu-menu"></a>
#### `Framework\UX\Menu\Menu`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Menu/Menu.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `direction` |  | `string $dir` |
| `horizontal` |  | — |
| `vertical` |  | — |
| `item` |  | `string $label`, `?string $href` = null, `?string $icon` = null, `bool $active` = false |
| `group` |  | `string $label`, `?string $icon` = null, `bool $open` = false, `?string $id` = null |
| `subitem` |  | `string $label`, `?string $href` = null, `?string $icon` = null, `bool $active` = false |
| `divider` |  | — |


<a name="framework-ux-dialog-modal"></a>
#### `Framework\UX\Dialog\Modal`

Modal 弹窗组件

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Dialog/Modal.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `title` |  | `string $title` |
| `content` |  | `mixed $content` |
| `size` |  | `string $size` |
| `sm` |  | — |
| `lg` |  | — |
| `xl` |  | — |
| `fullscreen` |  | — |
| `closeable` |  | `bool $closeable` = true |
| `backdrop` |  | `bool $backdrop` = true |
| `centered` |  | `bool $centered` = true |
| `footer` |  | `mixed $footer` |
| `open` |  | `bool $open` = true |
| `trigger` |  | `string $label`, `string $variant` = 'primary' |


<a name="framework-ux-ui-navigate"></a>
#### `Framework\UX\UI\Navigate`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/UI/Navigate.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `href` |  | `string $href` |
| `text` |  | `string $text` |
| `fragment` |  | `string $name` |
| `target` |  | `string $target` |
| `blank` |  | — |
| `replace` |  | `bool $replace` = true |
| `icon` |  | `string $icon`, `string $position` = 'left' |
| `bi` |  | `string $name`, `string $position` = 'left' |
| `variant` |  | `string $variant` |
| `primary` |  | — |
| `secondary` |  | — |
| `danger` |  | — |
| `success` |  | — |
| `warning` |  | — |
| `size` |  | `string $size` |
| `sm` |  | — |
| `lg` |  | — |
| `disabled` |  | `bool $disabled` = true |
| `state` |  | `string $key`, `mixed $value` |


<a name="framework-ux-navigation-pagination"></a>
#### `Framework\UX\Navigation\Pagination`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Navigation/Pagination.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `total` |  | `int $total` |
| `current` |  | `int $current` |
| `perPage` |  | `int $perPage` |
| `baseUrl` |  | `string $baseUrl` |
| `perPageOptions` |  | `array $options` |
| `perPageAction` |  | `string $action` |
| `showPerPage` |  | `int $total` = 0, `int $perPage` = 15, `int $current` = 1 |


<a name="framework-ux-richeditor-extensions-placeholderextension"></a>
#### `Framework\UX\RichEditor\Extensions\PlaceholderExtension`

**继承:** `Framework\UX\RichEditor\RichEditorExtension`  | **文件:** `php/src/UX/RichEditor/Extensions/PlaceholderExtension.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `getName` |  | — |
| `setPlaceholders` |  | `array $placeholders` |
| `addPlaceholder` |  | `string $key`, `string $label`, `mixed $defaultValue` = null |
| `execute` |  | `string $content`, `array $params` = [] |
| `parse` |  | `string $content` |
| `renderPreview` |  | `string $content` |
| `replaceInContent` |  | `string $content`, `array $values` |
| `getPlaceholders` |  | — |


<a name="framework-ux-overlay-popover"></a>
#### `Framework\UX\Overlay\Popover`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Overlay/Popover.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `title` |  | `string $title` |
| `content` |  | `string $content` |
| `placement` |  | `string $placement` |
| `top` |  | — |
| `bottom` |  | — |
| `left` |  | — |
| `right` |  | — |
| `trigger` |  | `string $trigger` |
| `hover` |  | — |
| `click` |  | — |
| `focus` |  | — |
| `arrow` |  | `bool $arrow` = true |
| `maxWidth` |  | `int $width` |
| `open` |  | `bool $open` = true |


<a name="framework-ux-feedback-progress"></a>
#### `Framework\UX\Feedback\Progress`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Feedback/Progress.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `value` |  | `int $value` |
| `max` |  | `int $max` |
| `variant` |  | `string $variant` |
| `primary` |  | — |
| `success` |  | — |
| `warning` |  | — |
| `danger` |  | — |
| `info` |  | — |
| `showLabel` |  | `bool $show` = true |
| `striped` |  | `bool $striped` = true |
| `animated` |  | `bool $animated` = true |
| `size` |  | `string $size` |
| `sm` |  | — |
| `lg` |  | — |


<a name="framework-ux-display-qrcode"></a>
#### `Framework\UX\Display\QRCode`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Display/QRCode.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `value` |  | `string $value` |
| `size` |  | `int $size` |
| `level` |  | `string $level` |
| `icon` |  | `string $icon`, `int $size` = 32 |
| `color` |  | `string $color` |
| `bgColor` |  | `string $bgColor` |


<a name="framework-ux-form-radio"></a>
#### `Framework\UX\Form\Radio`

**继承:** `Framework\UX\Form\FormField`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Form/Radio.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `options` |  | `array $options` |
| `inline` |  | `bool $inline` = true |


<a name="framework-ux-form-radiogroup"></a>
#### `Framework\UX\Form\RadioGroup`

**继承:** `Framework\UX\Form\FormField`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Form/RadioGroup.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `options` |  | `array $options` |
| `inline` |  | `bool $inline` = true |


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
| `action` | @ux-internal | `string $action` |
| `hoverAction` |  | `string $action` |


<a name="framework-ux-form-richeditor"></a>
#### `Framework\UX\Form\RichEditor`

**继承:** `Framework\UX\Form\FormField`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Form/RichEditor.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `liveModel` |  | `string $name` |
| `liveAction` |  | `string $action`, `string $event` = 'change' |
| `rows` |  | `int $rows` |
| `toolbar` |  | `array $items` |
| `minimal` |  | `bool $minimal` = true |
| `border` |  | `bool $border` = true |
| `width` |  | `string $width` |
| `height` |  | `string $height` |
| `placeholder` |  | `string $placeholder` |
| `outputFormat` |  | `string $format` |
| `extension` |  | `string $name`, `Framework\UX\RichEditor\RichEditorExtension $extension` |
| `parser` |  | `callable $parser`, `string $name` = 'default' |
| `parseContent` |  | `string $content`, `string $parserName` = 'default' |
| `sanitize` |  | `string $content` |


<a name="framework-ux-richeditor-richeditorextension"></a>
#### `Framework\UX\RichEditor\RichEditorExtension`

**abstract**  | **文件:** `php/src/UX/RichEditor/RichEditorExtension.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `getName` |  | — |
| `getToolbarButton` |  | `string $editorId` |
| `execute` |  | `string $content`, `array $params` = [] |
| `parse` |  | `string $content` |
| `renderPreview` |  | `string $content` |
| `getConfig` |  | `string $key`, `mixed $default` = null |
| `setConfig` |  | `string $key`, `mixed $value` |


<a name="framework-ux-layout-row"></a>
#### `Framework\UX\Layout\Row`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Layout/Row.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `justify` |  | `string $justify` |
| `justifyStart` |  | — |
| `justifyCenter` |  | — |
| `justifyEnd` |  | — |
| `justifyBetween` |  | — |
| `align` |  | `string $align` |
| `alignStart` |  | — |
| `alignCenter` |  | — |
| `alignEnd` |  | — |
| `gap` |  | `int $gap` |
| `wrap` |  | `bool $wrap` = true |
| `noWrap` |  | — |


<a name="framework-ux-form-searchinput"></a>
#### `Framework\UX\Form\SearchInput`

**继承:** `Framework\UX\Form\FormField`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Form/SearchInput.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `endpoint` |  | `string $url` |
| `options` |  | `array $options` |


<a name="framework-ux-form-select"></a>
#### `Framework\UX\Form\Select`

**继承:** `Framework\UX\Form\FormField`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Form/Select.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `options` |  | `array $options` |
| `multiple` |  | `bool $multiple` = true |
| `emptyOption` |  | `string $text` |
| `placeholder` |  | `string $placeholder` |


<a name="framework-ux-feedback-skeleton"></a>
#### `Framework\UX\Feedback\Skeleton`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Feedback/Skeleton.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `type` |  | `string $type` |
| `text` |  | — |
| `avatar` |  | — |
| `rect` |  | — |
| `circle` |  | — |
| `count` |  | `int $count` |
| `animated` |  | `bool $animated` = true |
| `width` |  | `string $width` |
| `height` |  | `string $height` |


<a name="framework-ux-form-slider"></a>
#### `Framework\UX\Form\Slider`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Form/Slider.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `min` |  | `float $min` |
| `max` |  | `float $max` |
| `value` |  | `float $value` |
| `step` |  | `float $step` |
| `disabled` |  | `bool $disabled` = true |
| `vertical` |  | `bool $vertical` = true |
| `range` |  | `bool $range` = true |
| `rangeValue` |  | `float $start`, `float $end` |
| `showTooltip` |  | `bool $show` = true |
| `action` |  | `string $action` |
| `format` |  | `string $format` |


<a name="framework-ux-display-statcard"></a>
#### `Framework\UX\Display\StatCard`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Display/StatCard.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `title` |  | `string $title` |
| `value` |  | `string $value` |
| `description` |  | `string $description` |
| `icon` |  | `string $icon` |
| `trendUp` |  | `string $value` |
| `trendDown` |  | `string $value` |
| `variant` |  | `string $variant` |


<a name="framework-ux-navigation-steps"></a>
#### `Framework\UX\Navigation\Steps`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Navigation/Steps.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `item` |  | `string $title`, `?string $description` = null |
| `current` |  | `int $current` |
| `vertical` |  | — |


<a name="framework-ux-form-switchfield"></a>
#### `Framework\UX\Form\SwitchField`

**继承:** `Framework\UX\Form\FormField`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Form/SwitchField.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `checked` |  | `bool $checked` = true |
| `onText` |  | `string $text` |
| `offText` |  | `string $text` |


<a name="framework-ux-navigation-tabs"></a>
#### `Framework\UX\Navigation\Tabs`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Navigation/Tabs.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `item` |  | `string $label`, `mixed $content`, `?string $id` = null, `bool $active` = false |
| `activeTab` |  | `string $id` |
| `variant` |  | `string $variant` |
| `liveModel` |  | `string $property` |
| `line` |  | — |
| `pills` |  | — |
| `justified` |  | `bool $justified` = true |


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

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Form/TagInput.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `value` |  | `array $value` |
| `placeholder` |  | `string $placeholder` |
| `disabled` |  | `bool $disabled` = true |
| `maxCount` |  | `int $max` |
| `action` |  | `string $action` |
| `allowClear` |  | `bool $allow` = true |


<a name="framework-ux-form-textarea"></a>
#### `Framework\UX\Form\Textarea`

**继承:** `Framework\UX\Form\FormField`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Form/Textarea.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `rows` |  | `int $rows` |


<a name="framework-ux-display-timeline"></a>
#### `Framework\UX\Display\Timeline`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Display/Timeline.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `item` |  | `string $content`, `?string $label` = null, `?string $dot` = null, `string $color` = 'blue' |
| `items` |  | `array $items` |
| `reverse` |  | `bool $reverse` = true |
| `mode` |  | `string $mode` |
| `left` |  | — |
| `right` |  | — |
| `alternate` |  | — |


<a name="framework-ux-dialog-toast"></a>
#### `Framework\UX\Dialog\Toast`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Dialog/Toast.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `message` |  | `string $message` |
| `type` |  | `string $type` |
| `success` |  | — |
| `error` |  | — |
| `warning` |  | — |
| `info` |  | — |
| `duration` |  | `int $ms` |
| `closeable` |  | `bool $closeable` = true |
| `title` |  | `string $title` |
| `icon` |  | `string $icon` |
| `position` |  | `string $position` |
| `topRight` |  | — |
| `topLeft` |  | — |
| `bottomRight` |  | — |
| `bottomLeft` |  | — |
| `script` |  | — |
| `container` |  | — |


<a name="framework-ux-overlay-tooltip"></a>
#### `Framework\UX\Overlay\Tooltip`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Overlay/Tooltip.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `content` |  | `string $content` |
| `placement` |  | `string $placement` |
| `top` |  | — |
| `bottom` |  | — |
| `left` |  | — |
| `right` |  | — |
| `trigger` |  | `string $trigger` |
| `hover` |  | — |
| `click` |  | — |
| `focus` |  | — |
| `arrow` |  | `bool $arrow` = true |
| `delay` |  | `int $delay` |
| `maxWidth` |  | `int $width` |


<a name="framework-ux-form-transfer"></a>
#### `Framework\UX\Form\Transfer`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Form/Transfer.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `dataSource` |  | `array $data` |
| `targetKeys` |  | `array $keys` |
| `titles` |  | `string $left`, `string $right` |
| `disabled` |  | `bool $disabled` = true |
| `showSearch` |  | `bool $show` = true |
| `action` |  | `string $action` |
| `searchPlaceholder` |  | `string $placeholder` |


<a name="framework-ux-form-treeselect"></a>
#### `Framework\UX\Form\TreeSelect`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Form/TreeSelect.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `treeData` |  | `array $data` |
| `value` |  | `string $value` |
| `placeholder` |  | `string $placeholder` |
| `multiple` |  | `bool $multiple` = true |
| `disabled` |  | `bool $disabled` = true |
| `allowClear` |  | `bool $allow` = true |
| `showSearch` |  | `bool $show` = true |
| `action` |  | `string $action` |
| `emptyText` |  | `string $text` |


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
| `child` | 添加子元素 | `mixed $child` |
| `children` | 批量添加子元素 | `mixed $children` |
| `liveAction` | 设置 Live Action，点击时触发后端方法 | `string $action`, `string $event` = 'click' |
| `on` | 绑定事件监听器 | `string $event`, `string $handler` |
| `onOpen` | 绑定 open 事件 | `string $handler` |
| `onClose` | 绑定 close 事件 | `string $handler` |
| `dispatch` | 派发自定义事件，绑定到 click | `string $event`, `?string $detail` = null |
| `render` | 渲染为 HTML 字符串 @ux-internal | — |


<a name="framework-ux-display-watermark"></a>
#### `Framework\UX\Display\Watermark`

**继承:** `Framework\UX\UXComponent`  | **实现:** `Stringable`  | **文件:** `php/src/UX/Display/Watermark.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `content` |  | `string $content` |
| `fontSize` |  | `int $size` |
| `fontColor` |  | `string $color` |
| `rotate` |  | `int $rotate` |
| `gap` |  | `int $x`, `int $y` |
| `zIndex` |  | `int $zIndex` |


