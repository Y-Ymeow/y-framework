# UX 组件系统

## 概述

UX 是在 View 基础上的高级组件封装，提供完整的交互功能。

**重要**: UX 组件现已完全与 View/Element 系统共生，所有组件底层使用 `Element` 构建 HTML，自动继承 View 系统的全部能力。

### 架构分层

- **View** (`src/View/`): HTML 元素基类，如 `Element`, `Container`, `Text` 等
- **UX** (`src/UX/`): 完整组件，底层使用 Element，提供语义化 API 和状态管理

### UX 与 View 共生关系

UX 组件通过 `UXComponent::toElement()` 方法构建 DOM 结构，自动获得以下能力：

| View/Element 能力 | UX 组件使用方式 |
|---|---|
| `bindModel()` 数据绑定 | `$input->bindModel('form.name')` |
| `bindOn()` 事件绑定 | `$btn->bindOn('click', 'count++')` |
| `bindIf()` 条件渲染 | `$el->bindIf('showDetail')` |
| `bindFor()` 循环渲染 | `$el->bindFor('item in items')` |
| `liveFragment()` 分片 | `$el->liveFragment('user-list')` |

### 循环渲染 (data-for)

`data-for` 允许根据数组或对象动态生成一组元素。该指令必须与 `<template>` 配合使用。

**示例代码：**
```php
Element::make('ul')
    ->attr('data-for', 'user in users')
    ->child(
        Element::make('template')->child(
            Element::make('li')
                ->attr('data-text', "($index + 1) + '. ' + user.name")
                ->attr('data-on:click', "alert('点击了 ' + user.name)")
        )
    );
```

**功能特性：**
- **内置变量**：循环内部自动提供当前项（如 `user`）和索引（`$index`）。
- **作用域链 (Scope Proxy)**：子元素通过 Proxy 代理访问作用域。它会优先查找局部变量（如 `user`），若找不到则查找组件状态。
- **响应式更新**：当 `users` 数组变化（如 push/pop 或后端 patches 更新）时，DOM 列表将自动同步重绘。

### 响应式作用域代理 ($)

在任何指令表达式中，你都可以使用 `$` 来显式访问当前作用域。系统采用响应式代理机制，确保赋值操作能准确回写到原始数据源。

**用法示例：**
```php
// 切换本地局部状态或组件属性
$btn->attr('data-on:click', '$.locale = $.locale === "zh" ? "en" : "zh"');
```
*注：即使不加 `$.` 前缀，指令系统也会自动通过作用域链进行查找并绑定响应式。*

### 实时属性同步 (data-live-model)

`data-live-model` 提供比标准 `data-model` 更强大的实时同步能力。

1. **双向绑定**：不仅能监听输入同步到后端，还能监听后端 `patches` 返回的值，实时更新 `<input>` 或 **RichEditor** 编辑区域的内容。
2. **防抖支持**：默认 300ms 防抖，可通过 `data-live-debounce` 自定义。
3. **后端钩子**：自动触发 `updated{PropertyName}` 钩子。

### 属性绑定 (data-bind:*)

`data-bind:*` 指令允许你动态绑定任何 HTML 属性。

```php
// 动态类名
$el->attr('data-bind:class', "{ 'active': active, 'disabled': !valid }");

// 动态样式
$el->attr('data-bind:style', "{ color: textColor, fontSize: '14px' }");

// 动态属性
$el->attr('data-bind:src', 'avatarUrl');
```
| `state()` 状态序列化 | `$el->state(['count' => $this->count])` |

```php
use Framework\View\Document\Document;
use Framework\UX\UI\Button;

$doc = Document::make('页面标题')
    ->ux()  // 加载 UX 资源
    ->main(Button::make()->label('点击我'));
```

## 与 Live 系统集成

UX 组件支持 Y (y-ui) 的 Live 能力：

### $dispatch 事件通信

```php
use Framework\UX\UI\Button;

$btn = Button::make()
    ->label('触发事件')
    ->dispatch('user:created', '{ id: 123 }');

$btn = Button::make()
    ->label('打开弹窗')
    ->openModal('my-modal');

$btn = Button::make()
    ->label('关闭')
    ->closeModal('my-modal');

$btn = Button::make()
    ->label('提示')
    ->showToast('操作成功', 'success');
```

### Live Action 调用

```php
$btn = Button::make()
    ->label('保存')
    ->liveAction('save', 'click');

$btn = Button::make()
    ->label('提交')
    ->submit()
    ->liveAction('submit', 'submit');
```

### 实时属性同步 (data-live-model)

`data-live-model` 提供比标准 `data-model` 更强大的实时同步能力。它会在用户输入时（带防抖）自动将值同步到后端的 PHP 属性。

**前端用法：**
```php
$input->attr('data-live-model', 'title');
// 或者
$editor->liveModel('content');
```

**后端钩子：**
当属性通过 `data-live-model` 更新时，`LiveComponent` 会自动触发以下生命周期钩子：
```php
class MyPage extends LiveComponent {
    public string $title = '';

    // 当 $title 改变时自动调用
    public function updatedTitle($newValue, $oldValue) {
        // 执行逻辑，如自动保存或验证
    }

    // 全局钩子
    public function updated($name, $value) {
        // 所有属性更新都会经过这里
    }
}
```

### 事件监听

```php
use Framework\UX\Dialog\Modal;

$modal = Modal::make()
    ->title('确认操作')
    ->content('确定要继续吗？')
    ->onOpen('console.log("Modal opened")')
    ->onClose('console.log("Modal closed")');
```

### data-on:* 事件绑定

```php
$btn = Button::make()
    ->label('点击')
    ->on('click', 'count++')
    ->on('mouseenter', 'hovered = true');
```

## UX 文件结构

```
src/UX/
├── UXComponent.php          # 组件基类（Element 驱动）
├── Dialog/                  # 对话框组件
│   ├── Drawer.php          # 抽屉组件
│   ├── Modal.php           # 模态框
│   └── Toast.php           # 消息提示
├── Form/                   # 表单组件
│   ├── FormField.php       # 表单字段基类
│   ├── FormBuilder.php     # 表单构建器
│   ├── Input.php           # 输入框
│   ├── Textarea.php        # 文本域
│   ├── Select.php          # 下拉选择
│   ├── Checkbox.php        # 复选框
│   ├── Radio.php           # 单选框
│   ├── RadioGroup.php      # 单选组
│   ├── SwitchField.php     # 开关
│   ├── SearchInput.php     # 搜索框
│   ├── FileUpload.php      # 文件上传
│   ├── RichEditor.php      # 富文本编辑器
│   ├── DatePicker.php      # 日期选择器（支持 showTime）
│   ├── DateRangePicker.php # 日期范围选择器（支持 showTime）
│   ├── TagInput.php        # 标签输入框
│   ├── Transfer.php        # 穿梭框
│   ├── TreeSelect.php      # 树选择
│   ├── Rate.php            # 评分
│   ├── Slider.php          # 滑块
│   └── ColorPicker.php     # 颜色选择器
├── Layout/                 # 布局组件
│   ├── Row.php             # Flex 行布局
│   ├── Grid.php            # 网格布局
│   └── Layout.php          # 页面布局
├── Data/                    # 数据展示组件
│   ├── DataTable.php       # 数据表格
│   ├── DataList.php        # 数据列表
│   ├── DataGrid.php        # 数据网格
│   ├── DataCard.php        # 数据卡片
│   ├── DataTree.php        # 树形控件
│   ├── DescriptionList.php # 描述列表
│   └── Calendar.php        # 日历
├── Display/                # 展示组件
│   ├── Badge.php           # 徽章
│   ├── ListView.php        # 列表视图
│   ├── Divider.php         # 分割线
│   └── Tag.php             # 标签
├── Feedback/               # 反馈组件
│   ├── EmptyState.php      # 空状态
│   ├── Tooltip.php         # 文字提示
│   ├── Popover.php         # 气泡卡片
│   ├── Collapse.php        # 折叠面板
│   └── Rate.php            # 评分
├── Media/                  # 媒体组件
│   ├── Carousel.php        # 轮播图
│   └── QRCode.php          # 二维码
├── Menu/                   # 菜单组件
│   └── Dropdown.php        # 下拉菜单
└── UI/                     # UI 组件
    ├── Accordion.php       # 手风琴
    ├── Alert.php           # 警告框
    ├── Avatar.php          # 头像
    ├── Button.php          # 按钮
    ├── Card.php            # 卡片
    ├── Breadcrumb.php      # 面包屑
    ├── Pagination.php      # 分页
    ├── Progress.php        # 进度条
    ├── Skeleton.php        # 骨架屏
    ├── StatCard.php        # 统计卡片
    ├── Steps.php           # 步骤条
    └── Tabs.php            # 选项卡
```

## Button 按钮

```php
use Framework\UX\UI\Button;

// 基本用法
$btn = Button::make()->label('提交');

// 链式调用
$btn = Button::make()
    ->label('保存')
    ->primary()      // variant('primary')
    ->lg()           // size('lg')
    ->icon('💾')     // 添加图标
    ->loading(true); // 显示加载状态

// 变体
$btn->primary();
$btn->secondary();
$btn->danger();
$btn->success();
$btn->warning();

// 尺寸
$btn->sm();
$btn->lg();

// 其他选项
$btn->outline();     // 边框样式
$btn->block();       // 全宽
$btn->disabled();    // 禁用
$btn->submit();      // type="submit"

// Live 集成
$btn->liveAction('save', 'click');

// Element 能力 - 数据绑定
$btn->bindModel('form.submitCount');

// Element 能力 - 事件绑定
$btn->bindOn('click', 'handleClick()');

// $dispatch 快捷方法
$btn->openModal('modal-id');
$btn->closeModal('modal-id');
$btn->showToast('操作成功', 'success');
$btn->dispatch('custom:event', '{ data: 1 }');
```

## Modal 模态框

```php
use Framework\UX\Dialog\Modal;
use Framework\UX\UI\Button;

$modal = Modal::make()
    ->id('confirm-modal')
    ->title('确认删除')
    ->content('确定要删除这条记录吗？此操作不可撤销。')
    ->size('lg')
    ->footer(
        Button::make()->label('取消')->secondary()->closeModal('confirm-modal') .
        Button::make()->label('确认删除')->danger()->liveAction('delete')
    );

// 触发按钮
echo $modal->trigger('打开弹窗', 'primary');

// 渲染模态框
echo $modal->render();

// 尺寸
$modal->sm();
$modal->lg();
$modal->xl();
$modal->fullscreen();

// 选项
$modal->closeable(false);  // 禁用关闭按钮
$modal->backdrop(false);   // 禁用背景遮罩
$modal->centered();        // 垂直居中
$modal->open();            // 默认打开

// 事件监听
$modal->onOpen('console.log("opened")');
$modal->onClose('console.log("closed")');
```

## Toast 消息提示

```php
use Framework\UX\Dialog\Toast;

// 基本用法
$toast = Toast::make()
    ->message('操作成功')
    ->success()
    ->duration(3000);

// 带标题
$toast = Toast::make()
    ->title('成功')
    ->message('数据已保存')
    ->success();

// 类型
$toast->success();
$toast->error();
$toast->warning();
$toast->info();

// 位置
$toast->topRight();
$toast->topLeft();
$toast->bottomRight();
$toast->bottomLeft();
```

## Drawer 抽屉

```php
use Framework\UX\Dialog\Drawer;

// 左侧抽屉
$drawer = Drawer::make()
    ->id('left-drawer')
    ->left()
    ->title('左侧抽屉')
    ->child('<p>抽屉内容</p>');

// 右侧抽屉
$drawer = Drawer::make()
    ->id('right-drawer')
    ->right()
    ->lg()
    ->title('右侧抽屉')
    ->child('<p>抽屉内容</p>');

// 方向
$drawer->left();
$drawer->right();
$drawer->top();
$drawer->bottom();

// 尺寸
$drawer->sm();
$drawer->md();
$drawer->lg();
$drawer->xl();
$drawer->full();

// 触发按钮
echo $drawer->trigger('打开抽屉', 'primary');

// 渲染
echo $drawer->render();
```

## Alert 警告框

```php
use Framework\UX\UI\Alert;

$alert = Alert::make()
    ->message('请完善您的个人资料')
    ->warning()
    ->title('注意')
    ->dismissible();

// 类型
$alert->success();
$alert->error();
$alert->warning();
$alert->info();
```

## Dropdown 下拉菜单

```php
use Framework\UX\Menu\Dropdown;

$dropdown = Dropdown::make()
    ->label('操作')
    ->item('查看', '/view')
    ->item('编辑', '/edit')
    ->divider()
    ->item('删除', null, 'delete')
    ->position('bottom-end');

// 位置
$dropdown->position('bottom-start');
$dropdown->position('bottom-end');
$dropdown->position('top-start');
$dropdown->position('top-end');

// 悬停触发
$dropdown->hover(true);
```

## Badge 徽章

```php
use Framework\UX\Display\Badge;

// 基本徽章
$badge = Badge::make()->text('Primary')->primary();

// Pill 样式
$badge = Badge::make()->text('New')->danger()->pill();

// 带圆点
$badge = Badge::make()->text('消息')->primary()->dot();

// 类型
$badge->default();
$badge->primary();
$badge->success();
$badge->warning();
$badge->danger();
$badge->info();

// 尺寸
$badge->sm();
$badge->md();
$badge->lg();
```

## Progress 进度条

```php
use Framework\UX\UI\Progress;

// 基本用法
$progress = Progress::make()
    ->value(25)
    ->primary();

// 带标签
$progress = Progress::make()
    ->value(50)
    ->success()
    ->showLabel();

// 条纹动画
$progress = Progress::make()
    ->value(75)
    ->warning()
    ->striped()
    ->animated();

// 尺寸
$progress->sm();
$progress->md();
$progress->lg();
```

## Layout 布局组件

### Row 行布局

```php
use Framework\UX\Layout\Row;

$row = Row::make()
    ->gap(4)
    ->justifyCenter()
    ->alignCenter()
    ->wrap()
    ->children(
        Container::make()->child('Item 1'),
        Container::make()->child('Item 2'),
        Container::make()->child('Item 3')
    );
```

### Grid 网格布局

```php
use Framework\UX\Layout\Grid;

$grid = Grid::make()
    ->cols(3)
    ->gap(4)
    ->alignCenter()
    ->children(
        Container::make()->child('Col 1'),
        Container::make()->child('Col 2'),
        Container::make()->child('Col 3')
    );
```

### Layout 页面布局

```php
use Framework\UX\Layout\Layout;

$layout = Layout::make()
    ->sidebarLeft()
    ->sidebarWidth(64)
    ->header()
    ->footer();

// 渲染各部分
echo $layout->renderHeader('<h1>标题</h1>');
echo $layout->renderBody('<nav>侧边栏</nav>', '<main>主内容</main>');
echo $layout->renderFooter('<p>页脚</p>');
```

## FormBuilder 表单构建器

```php
use Framework\UX\Form\FormBuilder;

$form = FormBuilder::make()
    ->post()
    ->action('/users/store')
    ->text('name', '姓名', ['required' => true])
    ->email('email', '邮箱', ['required' => true])
    ->password('password', '密码', ['required' => true])
    ->number('age', '年龄', ['min' => 0, 'max' => 150])
    ->textarea('bio', '简介', ['placeholder' => '介绍一下自己'])
    ->select('role', '角色', ['required' => true], [
        'user' => '普通用户',
        'admin' => '管理员',
    ])
    ->checkbox('agree', '同意条款')
    ->file('avatar', '头像')
    ->hidden('token', csrf_token())
    ->submitLabel('注册');

// 方法
$form->get();
$form->post();
$form->put();
$form->delete();

// 文件上传
$form->multipart();
```

## 表单字段

### Input 输入框

```php
use Framework\UX\Form\Input;

$input = Input::make()
    ->name('username')
    ->label('用户名')
    ->required()
    ->placeholder('请输入用户名');

// 类型
$input->email();
$input->password();
$input->number();
$input->tel();
$input->url();
$input->datetime();
$input->date();
$input->time();
$input->search();
```

### Textarea 文本域

```php
use Framework\UX\Form\Textarea;

$textarea = Textarea::make()
    ->name('bio')
    ->label('简介')
    ->rows(5)
    ->placeholder('介绍一下自己');
```

### Select 下拉选择

```php
use Framework\UX\Form\Select;

$select = Select::make()
    ->name('role')
    ->label('角色')
    ->options([
        'user' => '普通用户',
        'admin' => '管理员',
    ])
    ->value('user')
    ->multiple();

// 双向绑定
$select->bindModel('form.role');
```

### Checkbox 复选框

```php
use Framework\UX\Form\Checkbox;

$checkbox = Checkbox::make()
    ->name('agree')
    ->label('同意条款')
    ->checked();
```

### RadioGroup 单选组

```php
use Framework\UX\Form\RadioGroup;

$radio = RadioGroup::make()
    ->name('gender')
    ->label('性别')
    ->options(['male' => '男', 'female' => '女'])
    ->value('male')
    ->inline();
```

### SwitchField 开关

```php
use Framework\UX\Form\SwitchField;

$switch = SwitchField::make()
    ->name('enable')
    ->label('启用功能')
    ->checked()
    ->onText('开')
    ->offText('关');
```

### SearchInput 搜索框

```php
use Framework\UX\Form\SearchInput;

$search = SearchInput::make()
    ->name('search')
    ->placeholder('搜索用户...')
    ->endpoint('/api/search/users');
```

### FileUpload 文件上传

```php
use Framework\UX\Form\FileUpload;

$file = FileUpload::make()
    ->name('avatar')
    ->label('头像')
    ->accept('image/*')
    ->multiple();

// 快捷方式
$file->images();    // accept('image/*')
$file->documents(); // accept('.pdf,.doc,.docx,.xls,.xlsx')
```

### RichEditor 富文本编辑器

基于 PHP 配置的高级富文本编辑器，内置了图片插入、链接管理和多种扩展功能。

```php
use Framework\UX\Form\RichEditor;

// 基本用法
$editor = RichEditor::make()
    ->name('content')
    ->label('文章内容')
    ->value($this->content);

// 简洁模式 (默认无工具栏、无边框)
$editor->minimal();

// 简洁模式 + 边框
$editor->minimal()->border();

// 设置尺寸
$editor->width('100%')
    ->height('400px');

// 实时属性同步 (核心功能)
$editor->liveModel('content');

// 自定义工具栏
$editor->toolbar(['bold', 'italic', 'underline', 'strike', '|', 'heading', 'link', 'image']);
```

**专用 Modal 交互**：
RichEditor 内置了与组件风格统一的轻量级 Modal，用于处理链接插入和图片上传，无需依赖外部 Modal 组件，确保了 UI 的一致性和加载速度。

**扩展支持**：
可以通过 PHP 扩展轻松实现 @提及、表情包等高级功能。
```php
$editor->extension('mention', new MentionExtension(...));
```

## JavaScript API

UX 提供全局 `UX` 对象，与 Y (y-ui) 完全集成：

```javascript
// Modal
UX.modal.open('modal-id');
UX.modal.close();

// 通过 $dispatch
$dispatch('modal:open', { id: 'modal-id' });
$dispatch('modal:close', { id: 'modal-id' });

// Drawer
UX.drawer.open('drawer-id');
UX.drawer.close('drawer-id');

// Toast
UX.toast.success('操作成功');
UX.toast.error('发生错误', 5000, '错误标题');
UX.toast.warning('警告信息');
UX.toast.info('提示信息');
UX.toast.position('bottom-right');

// 通过 $dispatch
$dispatch('toast:show', { type: 'success', message: '操作成功' });

// Button
UX.button.loading('btn-id', true);  // 开始加载
UX.button.loading('btn-id', false); // 停止加载

// Form
UX.form.validate(formElement);
UX.form.serialize(formElement);
UX.form.clear(formElement);
UX.form.setData(formElement, {name: 'John'});

// Alert
UX.alert.dismiss('alert-id');

// Dropdown
UX.dropdown.toggle('dropdown-id');
UX.dropdown.close('dropdown-id');
```

## Live Operation 支持

UX 组件响应 Y 的 `executeOperation`：

```php
// 在 LiveComponent 中
#[LiveAction]
public function save(): void
{
    // 保存逻辑...
    
    // 打开模态框
    $this->operation('openModal', ['id' => 'success-modal']);
    
    // 显示 Toast
    $this->operation('toast', [
        'type' => 'success',
        'message' => '保存成功！',
        'duration' => 3000
    ]);
}
```

支持的 Operations：
- `openModal` - 打开模态框
- `closeModal` - 关闭模态框
- `toast` - 显示 Toast 消息
- `alert` - 关闭警告框
- `dropdown` - 切换/关闭下拉菜单
- `drawer:open` - 打开抽屉
- `drawer:close` - 关闭抽屉

## 与 LiveComponent 完整集成示例

```php
use Framework\Component\LiveComponent;
use Framework\Component\Attribute\LiveAction;
use Framework\UX\UI\Button;
use Framework\UX\Dialog\Modal;
use Framework\UX\Form\FormBuilder;

class UserForm extends LiveComponent
{
    public string $name = '';
    public string $email = '';
    public bool $saved = false;
    
    #[LiveAction]
    public function save(): void
    {
        // 保存逻辑...
        $this->saved = true;
        
        // 显示成功提示
        $this->operation('toast', [
            'type' => 'success',
            'message' => '用户保存成功！'
        ]);
        
        // 关闭模态框
        $this->operation('closeModal', ['id' => 'user-form-modal']);
    }
    
    public function render(): string
    {
        $form = FormBuilder::make()
            ->post()
            ->text('name', '姓名')
            ->liveBind('name', 'name')
            ->email('email', '邮箱')
            ->liveBind('email', 'email');
            
        $submitBtn = Button::make()
            ->label('保存')
            ->primary()
            ->liveAction('save');
            
        return $form->render() . $submitBtn->render();
    }
}
```

## 资源文件

```
public/assets/ux/
├── ux.css            # 样式文件
└── ux.js             # 脚本文件（与 Y 集成）
```

## AssetRegistry 资源注册

统一管理页面资源，避免重复加载：

```php
use Framework\View\Document\AssetRegistry;

$registry = AssetRegistry::getInstance();

// 注册 CSS
$registry->css('/assets/css/custom.css', 'custom-css');

// 注册 JS
$registry->js('/assets/js/ux.js', true, 'app-js');

// 内联样式
$registry->inlineStyle('.custom { color: red; }');

// 内联脚本
$registry->inlineScript('console.log("Hello");');

// 加载 UX 资源
$registry->ux();

// 渲染
echo $registry->renderCss();
echo $registry->renderJs();
```

## StatCard 统计卡片

```php
use Framework\UX\UI\StatCard;

$stat = StatCard::make()
    ->title('总销售额')
    ->value('￥128,430')
    ->icon('💰')
    ->trendUp('12.5%')
    ->description('较上月');

// 趋势
$stat->trendUp('12.5%');
$stat->trendDown('2.1%');
```

## Card 基础卡片

```php
use Framework\UX\UI\Card;

$card = Card::make()
    ->title('卡片标题')
    ->subtitle('子标题')
    ->image('/path/to/img.jpg')
    ->footer(Button::make()->label('确定'));

// 变体
$card->bordered();
$card->shadow();
$card->flat();
```

## Tabs 选项卡

```php
use Framework\UX\UI\Tabs;

$tabs = Tabs::make()
    ->item('标签1', '内容1')
    ->item('标签2', '内容2', 'tab-2', true); // 设置为 active

// 样式
$tabs->line();
$tabs->pills();
$tabs->justified();

// Live 集成
$tabs->liveModel('activeTab');
$tabs->liveAction('switchTab');

// 获取 Element 进一步操作
$element = $tabs->toElement();
$element->bindIf('showTabs');
```

## Accordion 手风琴

```php
use Framework\UX\UI\Accordion;

$accordion = Accordion::make()
    ->item('标题1', '内容1')
    ->item('标题2', '内容2', null, true); // 默认展开

// 选项
$accordion->multiple(); // 允许同时展开多个

// Live 集成
$accordion->liveAction('toggleAccordion');
```

## Breadcrumb 面包屑

```php
use Framework\UX\UI\Breadcrumb;

$breadcrumb = Breadcrumb::make()
    ->item('首页', '/')
    ->item('用户管理', '/users')
    ->item('编辑用户');
```

## Avatar 头像

```php
use Framework\UX\UI\Avatar;

$avatar = Avatar::make()
    ->src('/path/to/avatar.jpg')
    ->name('John Doe')
    ->status('online');

// 形状与尺寸
$avatar->circle();
$avatar->rounded();
$avatar->size('lg'); // xs, sm, md, lg, xl
```

## Steps 步骤条

```php
use Framework\UX\UI\Steps;

$steps = Steps::make()
    ->current(1)
    ->item('步骤1', '描述1')
    ->item('步骤2', '描述2')
    ->item('步骤3');

// 方向
$steps->vertical();
```

## Pagination 分页

```php
use Framework\UX\UI\Pagination;

$pagination = Pagination::make()
    ->total(100)
    ->current(1)
    ->perPage(15)
    ->baseUrl('/users');

// Live 集成
$pagination->liveAction('loadPage');
```

## Skeleton 骨架屏

```php
use Framework\UX\UI\Skeleton;

$skeleton = Skeleton::make()->text()->count(3);
$skeleton = Skeleton::make()->avatar()->animated();
$skeleton = Skeleton::make()->rect()->height('200px');
```

## Live 深度集成

### 分片更新 (Fragment Update)

这是框架推荐的局部刷新方式，相比于 `x-text` 指令，它能保持 PHP 端渲染的完整逻辑。

**1. 在渲染方法中标记分片：**
```php
public function render(): string
{
    return Text::div()->children(
        Text::p('计数: ' . Text::strong((string)$this->count)->liveFragment('counter-box')),
        Button::make()->label('增加')->liveAction('increment')
    )->render();
}
```

**2. 在 Action 中触发刷新：**
```php
#[LiveAction]
public function increment(): void
{
    $this->count++;
    $this->refresh('counter-box'); // 标记需要自动收集的分片名称
}
```

### 通用 UX 指令

你可以通过 `LiveComponent` 内置的方法从服务端直接控制前端组件：

```php
$this->toast('操作成功');
$this->openModal('user-modal');
$this->selectTab('main-tabs', 'security');
$this->toggleAccordion('item-1', true);

// 通用指令格式
$this->ux(string $component, string $id, string $action, array $data = []);
```

## Data 数据展示组件

### DataTable 数据表格

类似 Ant Design 的 Table 组件，支持列定义、自定义渲染、排序、选择等功能。

```php
use Framework\UX\Data\DataTable;
use Framework\UX\Display\Badge;
use Framework\UX\UI\Button;

// 基本用法
$table = DataTable::make()
    ->column('name', '姓名')
    ->column('email', '邮箱')
    ->column('role', '角色')
    ->dataSource([
        ['id' => 1, 'name' => '张三', 'email' => 'zhang@example.com', 'role' => 'admin'],
        ['id' => 2, 'name' => '李四', 'email' => 'li@example.com', 'role' => 'user'],
    ]);

// 自定义列渲染
$table = DataTable::make()
    ->column('name', '姓名')
    ->column('status', '状态', fn($val) => Badge::make()->text($val ? '启用' : '禁用')->$val ? 'success' : 'danger'())
    ->column('action', '操作', fn($val, $row) => Button::make()->label('编辑')->sm())
    ->dataSource($users);

// 列定义选项
$table->column('name', '姓名', null, [
    'width' => '200px',
    'align' => 'center',
    'sortable' => true,
    'fixed' => 'left',
]);

// 批量列定义
$table->columns([
    ['dataKey' => 'name', 'title' => '姓名', 'sortable' => true],
    ['dataKey' => 'email', 'title' => '邮箱'],
    ['dataKey' => 'role', 'title' => '角色', 'render' => fn($v) => $v],
]);

// 样式
$table->striped();       // 斑马纹
$table->bordered();      // 边框
$table->hoverable();     // 悬停高亮（默认开启）
$table->sm();            // 紧凑尺寸
$table->lg();            // 宽松尺寸

// 行选择
$table->selectable();    // 启用复选框选择

// 空数据提示
$table->emptyText('没有找到相关数据');

// 标题和额外内容
$table->title('用户列表');
$table->header(Button::make()->label('新增用户')->primary());

// 行属性回调
$table->rowCallback(fn($row, $index) => $row['active'] ? 'ux-row-active' : 'ux-row-inactive');

// 分页
$table->pagination(total: 100, current: 1, perPage: 15, baseUrl: '/users');

// 表尾
$table->footer('合计: 100 条记录');
```

#### DataTable 与 LiveComponent 集成

DataTable 完全支持 LiveComponent 的交互模式，通过 `liveAction` 通道实现排序、翻页、选择等操作：

```php
use Framework\UX\Data\DataTable;

// 在 LiveComponent 的 render() 方法中
class UserTable extends LiveComponent
{
    public string $sortField = 'name';
    public string $sortDirection = 'asc';
    public int $page = 1;
    public array $selectedKeys = [];

    #[LiveAction]
    public function sort(): void
    {
        $this->sortField = $this->params['sortField'];
        $this->sortDirection = $this->params['sortDirection'];
        $this->refresh('user-table-body');
    }

    #[LiveAction]
    public function loadPage(): void
    {
        $this->page = $this->params['page'];
        $this->refresh('user-table-body');
    }

    #[LiveAction]
    public function selectRow(): void
    {
        $key = $this->params['rowKey'];
        // 处理选择逻辑...
        $this->refresh('user-table-body');
    }

    public function render(): string
    {
        return DataTable::make()
            ->column('name', '姓名', null, ['sortable' => true])
            ->column('email', '邮箱')
            ->column('role', '角色')
            ->dataSource($this->getUsers())
            ->selectable()
            ->sortField($this->sortField)
            ->sortDirection($this->sortDirection)
            ->fragment('user-table-body')   // tbody 标记为 live-fragment，支持局部刷新
            ->sortAction('sort')            // 点击排序列触发 LiveAction
            ->pageAction('loadPage')        // 点击翻页触发 LiveAction
            ->selectAction('selectRow')     // 勾选行触发 LiveAction
            ->rowAction('selectRow')        // 点击行触发 LiveAction
            ->pagination(100, $this->page, 15)
            ->render();
    }
}
```

**Live 集成要点：**

| 方法 | 作用 | 生成的 HTML 属性 |
|---|---|---|
| `fragment(name)` | 标记 tbody 为可局部刷新的分片 | `data-live-fragment="name"` |
| `sortAction(action)` | 排序列点击触发 LiveAction | `data-action="sort"` + `data-action-params` |
| `pageAction(action)` | 翻页触发 LiveAction | Pagination 内部 `data-action` |
| `selectAction(action)` | 行选择触发 LiveAction | checkbox `data-action="select"` |
| `rowAction(action, event)` | 行点击触发 LiveAction | tr `data-action="rowAction"` |
| `sortField(field)` | 当前排序字段（控制排序指示器） | CSS class `ux-data-table-sorted` |
| `sortDirection(dir)` | 当前排序方向 | CSS class `ux-data-table-sort-asc/desc` |

> **注意**：如果未指定 `sortAction`/`pageAction`/`selectAction`，但设置了 `liveAction()`，则自动 fallback 到 `liveAction`。

### DataList 数据列表

以列表形式展示数据，支持自定义渲染每一项。

```php
use Framework\UX\Data\DataList;

// 基本用法
$list = DataList::make()
    ->dataSource(['项目A', '项目B', '项目C']);

// 自定义渲染
$list = DataList::make()
    ->renderItem(fn($item, $index) => '<div class="item-title">' . $item['title'] . '</div>')
    ->dataSource($items);

// 样式
$list->bordered();       // 边框
$list->split(false);     // 去掉分割线（默认有）
$list->sm();             // 紧凑尺寸
$list->lg();             // 宽松尺寸

// 空数据
$list->emptyText('暂无内容');

// 标题和分页
$list->title('消息列表');
$list->pagination(50, 1, 10, '/messages');

// Live 集成
$list->fragment('msg-list');         // 标记为 live-fragment，支持局部刷新
$list->itemAction('openMessage');    // 点击列表项触发 LiveAction
$list->pageAction('loadPage');       // 翻页触发 LiveAction
```

### DataGrid 数据网格

以网格卡片形式展示数据，适合展示同类数据项。

```php
use Framework\UX\Data\DataGrid;

// 基本用法
$grid = DataGrid::make()
    ->cols(4)
    ->gap(6)
    ->renderItem(fn($item, $index) => Card::make()->title($item['name']))
    ->dataSource($products);

// 选项
$grid->cols(3);          // 列数
$grid->gap(4);           // 间距
$grid->emptyText('暂无商品');
$grid->title('商品列表');
$grid->pagination(100, 1, 12, '/products');

// Live 集成
$grid->fragment('product-grid');     // 标记为 live-fragment
$grid->itemAction('viewProduct');    // 点击卡片触发 LiveAction
$grid->pageAction('loadPage');       // 翻页触发 LiveAction
```

### DataCard 数据卡片

以卡片形式展示单条数据的详细信息。

```php
use Framework\UX\Data\DataCard;

// 基本用法
$card = DataCard::make()
    ->title('用户信息')
    ->field('姓名', 'name')
    ->field('邮箱', 'email')
    ->field('角色', 'role')
    ->field('状态', 'active', fn($v) => $v ? '启用' : '禁用')
    ->item([
        'name' => '张三',
        'email' => 'zhang@example.com',
        'role' => '管理员',
        'active' => true,
    ]);

// 带头像和操作
$card = DataCard::make()
    ->avatar(Avatar::make()->src('/avatar.jpg')->circle())
    ->title('name')
    ->subtitle('email')
    ->actions(Button::make()->label('编辑')->sm())
    ->field('部门', 'department')
    ->field('入职日期', 'hire_date')
    ->item($userData);

// 批量字段定义
$card->fields([
    ['label' => '姓名', 'dataKey' => 'name'],
    ['label' => '邮箱', 'dataKey' => 'email'],
    ['label' => '状态', 'dataKey' => 'status', 'render' => fn($v) => $v],
]);

// 样式
$card->bordered();

// Live 集成
$card->fragment('user-card');   // 标记为 live-fragment，支持局部刷新
```

### DataTree 树形控件

以树形结构展示层级数据。

```php
use Framework\UX\Data\DataTree;

// 基本用法
$tree = DataTree::make()
    ->treeData([
        [
            'key' => '1',
            'title' => '部门A',
            'children' => [
                ['key' => '1-1', 'title' => '团队1'],
                ['key' => '1-2', 'title' => '团队2'],
            ],
        ],
        ['key' => '2', 'title' => '部门B'],
    ]);

// 选项
$tree->showLine();              // 显示连接线
$tree->showIcon();              // 显示图标
$tree->selectable();            // 可选择
$tree->checkable();             // 可勾选
$tree->defaultExpandAll();      // 默认全部展开
$tree->defaultExpandedKeys(['1']); // 默认展开指定节点

// 自定义标题渲染
$tree->renderTitle(fn($node) => '<span class="tree-title">' . $node['title'] . '</span>');

// 空数据
$tree->emptyText('暂无数据');

// Live 集成
$tree->fragment('dept-tree');      // 标记为 live-fragment
$tree->toggleAction('toggleNode'); // 展开/折叠触发 LiveAction
$tree->selectAction('selectNode'); // 选中节点触发 LiveAction
$tree->checkAction('checkNode');   // 勾选节点触发 LiveAction
```

### DescriptionList 描述列表

以键值对形式展示数据详情，适合详情页面。

```php
use Framework\UX\Data\DescriptionList;

// 基本用法
$desc = DescriptionList::make()
    ->item('姓名', '张三')
    ->item('邮箱', 'zhang@example.com')
    ->item('角色', '管理员');

// 批量定义
$desc = DescriptionList::make()
    ->items([
        ['label' => '姓名', 'value' => '张三'],
        ['label' => '邮箱', 'value' => 'zhang@example.com'],
        ['label' => '角色', 'value' => '管理员'],
    ]);

// 列数
$desc->columns(2);     // 每行2列
$desc->columns(4);     // 每行4列

// 样式
$desc->bordered();     // 带边框
$desc->sm();           // 紧凑尺寸
$desc->lg();           // 宽松尺寸

// 标签对齐
$desc->labelAlign('left');   // 标签左对齐（默认右对齐）

// 自定义值渲染
$desc->item('状态', true, fn($v) => $v ? '启用' : '禁用');

// 标题和额外操作
$desc->title('用户详情');
$desc->extra(Button::make()->label('编辑')->sm());

// Live 集成
$desc->fragment('user-detail');   // 标记为 live-fragment，支持局部刷新
```

## DatePicker 日期选择器

支持日期选择和日期时间选择，内置日历面板、月份导航、范围限制。

```php
use Framework\UX\Form\DatePicker;

// 基本用法
$picker = DatePicker::make()
    ->placeholder('选择日期');

// 设置默认值
$picker = DatePicker::make()
    ->value('2026-05-02');

// 日期时间模式
$picker = DatePicker::make()
    ->showTime();
// 输出格式：2026-05-02 14:30:00

// 日期时间 + 默认时间
$picker = DatePicker::make()
    ->showTime()
    ->timeHour(9)
    ->timeMinute(0)
    ->timeSecond(0);
// 默认时间：09:00:00

// 日期范围限制
$picker = DatePicker::make()
    ->minDate('2026-01-01')
    ->maxDate('2026-12-31');

// 显示今天按钮
$picker = DatePicker::make()
    ->showToday();

// 自定义格式
$picker = DatePicker::make()
    ->format('Y/m/d');

// Live 双向绑定
$picker = DatePicker::make()
    ->liveModel('eventDate');
// 自动与 LiveComponent 的 $eventDate 属性同步
```

**API 参考：**

| 方法 | 说明 |
|---|---|
| `value(string $date)` | 设置默认值，格式 Y-m-d |
| `placeholder(string $text)` | 占位文本 |
| `format(string $format)` | 日期格式，默认 `Y-m-d` |
| `showTime(bool $show)` | 启用时间选择，格式自动切换为 `Y-m-d H:i:s` |
| `timeHour(int $h)` | 默认小时 (0-23) |
| `timeMinute(int $m)` | 默认分钟 (0-59) |
| `timeSecond(int $s)` | 默认秒数 (0-59) |
| `minDate(string $date)` | 最小可选日期 |
| `maxDate(string $date)` | 最大可选日期 |
| `showToday(bool $show)` | 显示"今天"快捷按钮 |
| `liveModel(string $property)` | 与 Live 属性双向绑定 |

**交互说明：**
- 普通模式：选日期后自动关闭面板，派发 `ux:change` 事件
- showTime 模式：选日期后面板保持打开，调整时/分/秒后点"确定"关闭

## DateRangePicker 日期范围选择器

双日历面板，支持范围选择和快捷预设。

```php
use Framework\UX\Form\DateRangePicker;

// 基本用法
$picker = DateRangePicker::make()
    ->placeholder('选择日期范围');

// 日期时间范围
$picker = DateRangePicker::make()
    ->showTime();
// 输出格式：2026-05-01 00:00:00 ~ 2026-05-02 23:59:59

// 自定义分隔符
$picker = DateRangePicker::make()
    ->separator('至');

// 日期范围限制
$picker = DateRangePicker::make()
    ->minDate('2026-01-01')
    ->maxDate('2026-12-31');

// Live 双向绑定
$picker = DateRangePicker::make()
    ->liveModel('dateRange');
// 自动与 LiveComponent 的 $dateRange 属性同步
```

**API 参考：**

| 方法 | 说明 |
|---|---|
| `startValue(string $date)` | 设置默认开始日期 |
| `endValue(string $date)` | 设置默认结束日期 |
| `placeholder(string $text)` | 占位文本 |
| `separator(string $sep)` | 分隔符，默认 `~` |
| `format(string $format)` | 日期格式 |
| `showTime(bool $show)` | 启用时间选择 |
| `minDate(string $date)` | 最小可选日期 |
| `maxDate(string $date)` | 最大可选日期 |
| `liveModel(string $property)` | 与 Live 属性双向绑定 |

**内置快捷选项：** 最近一周、最近一月、最近三月

## Calendar 日历

独立日历组件，支持月视图和年视图切换。

```php
use Framework\UX\Data\Calendar;

// 基本用法
$calendar = Calendar::make();

// 设置选中日期
$calendar = Calendar::make()
    ->value('2026-05-02');

// 日期范围限制
$calendar = Calendar::make()
    ->validRange(['start' => '2026-01-01', 'end' => '2026-12-31']);

// Live 双向绑定
$calendar = Calendar::make()
    ->liveModel('selectedDate');
```

**API 参考：**

| 方法 | 说明 |
|---|---|
| `value(string $date)` | 选中日期 |
| `validRange(array $range)` | 可选范围，`['start' => '...', 'end' => '...']` |
| `liveModel(string $property)` | 与 Live 属性双向绑定 |

## TagInput 标签输入框

支持输入、删除、搜索标签。

```php
use Framework\UX\Form\TagInput;

// 基本用法
$tagInput = TagInput::make()
    ->placeholder('输入标签后回车');

// 设置默认值
$tagInput = TagInput::make()
    ->value(['PHP', 'JavaScript', 'CSS']);

// 最大标签数
$tagInput = TagInput::make()
    ->maxTags(5);

// Live 双向绑定
$tagInput = TagInput::make()
    ->liveModel('tags');
// 值格式：逗号分隔字符串 "PHP,JavaScript,CSS"
```

**API 参考：**

| 方法 | 说明 |
|---|---|
| `value(array $tags)` | 默认标签数组 |
| `placeholder(string $text)` | 占位文本 |
| `maxTags(int $max)` | 最大标签数量 |
| `liveModel(string $property)` | 与 Live 属性双向绑定 |

## Transfer 穿梭框

双栏列表选择，支持搜索和批量移动。

```php
use Framework\UX\Form\Transfer;

// 基本用法
$transfer = Transfer::make()
    ->dataSource([
        ['key' => '1', 'title' => '选项1'],
        ['key' => '2', 'title' => '选项2'],
        ['key' => '3', 'title' => '选项3'],
    ])
    ->targetKeys(['2']);

// 自定义标题
$transfer = Transfer::make()
    ->titles(['可选列表', '已选列表'])
    ->dataSource($data)
    ->targetKeys($selected);

// 启用搜索
$transfer = Transfer::make()
    ->showSearch()
    ->searchPlaceholder('搜索...')
    ->dataSource($data);

// 禁用某些选项
$transfer = Transfer::make()
    ->dataSource([
        ['key' => '1', 'title' => '选项1', 'disabled' => true],
        ['key' => '2', 'title' => '选项2'],
    ]);

// Live 双向绑定
$transfer = Transfer::make()
    ->dataSource($data)
    ->liveModel('selectedKeys');
// 值格式：JSON 数组字符串 '["1","3"]'
```

**API 参考：**

| 方法 | 说明 |
|---|---|
| `dataSource(array $data)` | 数据源，每项需含 `key` 和 `title` |
| `targetKeys(array $keys)` | 右侧（已选）的 key 列表 |
| `titles(array $titles)` | 左右面板标题 |
| `showSearch(bool $show)` | 启用搜索 |
| `searchPlaceholder(string $text)` | 搜索框占位文本 |
| `liveModel(string $property)` | 与 Live 属性双向绑定 |

## TreeSelect 树选择

下拉树形选择器，支持展开折叠和搜索。

```php
use Framework\UX\Form\TreeSelect;

// 基本用法
$treeSelect = TreeSelect::make()
    ->placeholder('选择部门')
    ->treeData([
        [
            'key' => '1',
            'title' => '技术部',
            'children' => [
                ['key' => '1-1', 'title' => '前端组'],
                ['key' => '1-2', 'title' => '后端组'],
            ],
        ],
        ['key' => '2', 'title' => '产品部'],
    ]);

// 默认值
$treeSelect = TreeSelect::make()
    ->value('1-1')
    ->treeData($treeData);

// 启用搜索
$treeSelect = TreeSelect::make()
    ->showSearch()
    ->treeData($treeData);

// Live 双向绑定
$treeSelect = TreeSelect::make()
    ->treeData($treeData)
    ->liveModel('department');
```

**API 参考：**

| 方法 | 说明 |
|---|---|
| `treeData(array $data)` | 树形数据，每项含 `key`、`title`，可选 `children` |
| `value(string $key)` | 默认选中节点的 key |
| `placeholder(string $text)` | 占位文本 |
| `showSearch(bool $show)` | 启用搜索 |
| `emptyText(string $text)` | 空数据提示 |
| `liveModel(string $property)` | 与 Live 属性双向绑定 |

## Rate 评分

星级评分组件，支持半星和自定义图标。

```php
use Framework\UX\Form\Rate;

// 基本用法
$rate = Rate::make()
    ->count(5)
    ->value(3);

// 允许半星
$rate = Rate::make()
    ->allowHalf()
    ->value(3.5);

// 只读
$rate = Rate::make()
    ->value(4)
    ->readonly();

// 自定义图标
$rate = Rate::make()
    ->icon('bi-star-fill')
    ->emptyIcon('bi-star');

// Live 双向绑定
$rate = Rate::make()
    ->liveModel('rating');
```

**API 参考：**

| 方法 | 说明 |
|---|---|
| `count(int $count)` | 星星数量，默认 5 |
| `value(float $value)` | 默认评分值 |
| `allowHalf(bool $allow)` | 允许半星 |
| `readonly(bool $readonly)` | 只读模式 |
| `icon(string $icon)` | 选中图标类名 |
| `emptyIcon(string $icon)` | 未选中图标类名 |
| `liveModel(string $property)` | 与 Live 属性双向绑定 |

## Slider 滑块

滑动输入组件，支持范围选择。

```php
use Framework\UX\Form\Slider;

// 基本用法
$slider = Slider::make()
    ->min(0)
    ->max(100)
    ->value(30);

// 带步长
$slider = Slider::make()
    ->min(0)
    ->max(100)
    ->step(10)
    ->value(50);

// 范围选择
$slider = Slider::make()
    ->range()
    ->rangeValue([20, 80]);

// 显示标记
$slider = Slider::make()
    ->marks(['0' => '0°C', '25' => '25°C', '100' => '100°C']);

// Live 双向绑定
$slider = Slider::make()
    ->liveModel('volume');
```

**API 参考：**

| 方法 | 说明 |
|---|---|
| `min(int|float $min)` | 最小值，默认 0 |
| `max(int|float $max)` | 最大值，默认 100 |
| `step(int|float $step)` | 步长，默认 1 |
| `value(int|float $value)` | 默认值 |
| `range(bool $range)` | 范围选择模式 |
| `rangeValue(array $values)` | 范围模式的默认值 `[start, end]` |
| `marks(array $marks)` | 刻度标记 |
| `liveModel(string $property)` | 与 Live 属性双向绑定 |

## ColorPicker 颜色选择器

颜色选择组件，支持预设色板和自定义颜色。

```php
use Framework\UX\Form\ColorPicker;

// 基本用法
$picker = ColorPicker::make()
    ->value('#3b82f6');

// 预设颜色
$picker = ColorPicker::make()
    ->presets(['#ef4444', '#f97316', '#eab308', '#22c55e', '#3b82f6', '#8b5cf6']);

// 显示透明度
$picker = ColorPicker::make()
    ->showAlpha();

// Live 双向绑定
$picker = ColorPicker::make()
    ->liveModel('themeColor');
```

**API 参考：**

| 方法 | 说明 |
|---|---|
| `value(string $color)` | 默认颜色值 |
| `presets(array $colors)` | 预设颜色列表 |
| `showAlpha(bool $show)` | 显示透明度选项 |
| `liveModel(string $property)` | 与 Live 属性双向绑定 |

## Carousel 轮播图

图片轮播组件，支持自动播放和手动切换。

```php
use Framework\UX\Media\Carousel;

// 基本用法
$carousel = Carousel::make()
    ->items([
        ['image' => '/img/slide1.jpg', 'title' => '标题1'],
        ['image' => '/img/slide2.jpg', 'title' => '标题2'],
        ['image' => '/img/slide3.jpg', 'title' => '标题3'],
    ]);

// 自动播放
$carousel = Carousel::make()
    ->autoplay()
    ->interval(3000)
    ->items($slides);

// 选项
$carousel->dots();          // 显示指示点（默认开启）
$carousel->arrows();        // 显示箭头
$carousel->loop();          // 循环播放
$carousel->fade();          // 淡入淡出效果
```

**API 参考：**

| 方法 | 说明 |
|---|---|
| `items(array $items)` | 轮播项数据 |
| `autoplay(bool $autoplay)` | 自动播放 |
| `interval(int $ms)` | 自动播放间隔，默认 3000ms |
| `dots(bool $show)` | 显示指示点 |
| `arrows(bool $show)` | 显示前后箭头 |
| `loop(bool $loop)` | 循环播放 |
| `fade(bool $fade)` | 淡入淡出切换效果 |

## QRCode 二维码

基于 qrcode.js 库的二维码生成组件。

```php
use Framework\UX\Media\QRCode;

// 基本用法
$qrcode = QRCode::make()
    ->value('https://example.com');

// 自定义尺寸和颜色
$qrcode = QRCode::make()
    ->value('https://example.com')
    ->size(200)
    ->color('#000000')
    ->bgColor('#ffffff');

// 纠错级别
$qrcode = QRCode::make()
    ->value('https://example.com')
    ->level('H');  // L, M, Q, H
```

**API 参考：**

| 方法 | 说明 |
|---|---|
| `value(string $text)` | 二维码内容 |
| `size(int $px)` | 尺寸，默认 128 |
| `color(string $color)` | 前景色 |
| `bgColor(string $color)` | 背景色 |
| `level(string $level)` | 纠错级别：L/M/Q/H，默认 M |

## 其他展示组件

### Divider 分割线

```php
use Framework\UX\Display\Divider;

$divider = Divider::make();
$divider = Divider::make()->vertical();
$divider = Divider::make()->text('或者');
$divider = Divider::make()->dashed();
```

### EmptyState 空状态

```php
use Framework\UX\Feedback\EmptyState;

$empty = EmptyState::make()
    ->title('暂无数据')
    ->description('点击下方按钮添加新内容')
    ->icon('bi-inbox');
```

### Tag 标签

```php
use Framework\UX\Display\Tag;

$tag = Tag::make()->text('标签')->primary();
$tag = Tag::make()->text('成功')->success()->closable();
$tag = Tag::make()->text('警告')->warning()->outline();
```

### Tooltip 文字提示

```php
use Framework\UX\Feedback\Tooltip;

$tooltip = Tooltip::make()
    ->content('提示内容')
    ->child(Button::make()->label('悬停查看'));
```

### Popover 气泡卡片

```php
use Framework\UX\Feedback\Popover;

$popover = Popover::make()
    ->title('标题')
    ->content('详细内容')
    ->child(Button::make()->label('点击查看'));
```

### Collapse 折叠面板

```php
use Framework\UX\Feedback\Collapse;

$collapse = Collapse::make()
    ->item('面板1', '内容1')
    ->item('面板2', '内容2', true);  // 默认展开
```

### ListView 列表视图

```php
use Framework\UX\Display\ListView;

$list = ListView::make()
    ->items(['项目1', '项目2', '项目3'])
    ->bordered()
    ->split();
```

## UX-Live 桥接系统

UX 组件通过 `liveModel()` 方法与 LiveComponent 实现零配置双向绑定，无需为每个组件编写 LiveAction。

### 工作原理

```
UX 组件值变化 → 派发 ux:change 事件 → 桥接层更新隐藏 input → 触发 data-live-model → Live __updateProperty → 属性更新
Live 属性更新 → patches 返回 → 桥接层检测变化 → 调用组件 setValue() → UI 更新
```

### 使用方式

```php
// PHP 端：组件加 liveModel()
DatePicker::make()->liveModel('eventDate');
Rate::make()->liveModel('rating');
Transfer::make()->liveModel('selectedKeys');

// LiveComponent 端：声明公开属性即可
class MyForm extends LiveComponent {
    public string $eventDate = '';
    public float $rating = 0;
    public string $selectedKeys = '[]';
    
    // 不需要写任何 LiveAction！
    // __updateProperty 自动处理
    
    public function updatedEventDate($newValue, $oldValue) {
        // 属性变更后的业务逻辑
    }
}
```

### 支持的组件

| 组件 | liveModel 值格式 | 示例 |
|---|---|---|
| DatePicker | `Y-m-d` 或 `Y-m-d H:i:s` | `"2026-05-02"` |
| DateRangePicker | `start~end` | `"2026-05-01~2026-05-02"` |
| Calendar | `Y-m-d` | `"2026-05-02"` |
| TagInput | 逗号分隔 | `"PHP,JS,CSS"` |
| Transfer | JSON 数组 | `'["1","3"]'` |
| TreeSelect | 节点 key | `"1-2"` |
| Rate | 数字 | `"3.5"` |
| Slider | 数字或 JSON | `"30"` 或 `"[20,80]"` |
| ColorPicker | 颜色值 | `"#3b82f6"` |

### 命名空间约定

- `data-action` — 传给 Live 的 action（如 Dropdown 菜单项、表单提交）
- `data-ux-action` — UX 组件内部操作（如日历翻页、今天按钮），不会被 Live 拦截
- `data-ux-model` — UX 组件的 Live 桥接标记
- `data-live-model` — Live 的属性绑定（桥接层自动在隐藏 input 上设置）
