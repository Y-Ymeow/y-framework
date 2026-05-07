# PageBuilder Settings Schema 重构方案

## 目标

重构 `settingsSchema()` 方法，让它支持类似 Filament 的可嵌套组件化表单结构，提高灵活性和可扩展性。

## 核心设计理念

1. **向后兼容**：保留原有的数组格式，现有代码不受影响
2. **灵活支持**：支持三种返回类型：
   - 数组格式（原有的简单字段数组）
   - 单个 Element/UXComponent（直接渲染）
   - 可嵌套的组件树（使用新的表单构建器）
3. **可扩展性**：支持 Grid、Section、Tabs 等布局组件嵌套表单字段

## 架构设计

### 1. 创建新的 SettingsBuilder 类

类似于现有的 FormBuilder，但专为 PageBuilder 组件设置设计，核心特点：
- 链式 API
- 支持组件嵌套
- 自动绑定 `data-model` 到组件设置
- 与 LiveComponent 集成

```php
// 使用示例
public function settingsSchema(): Element|UXComponent|array|SettingsBuilder
{
    return SettingsBuilder::make()
        ->components([
            Grid::make(2)
                ->schema([
                    Section::make('基础设置')
                        ->schema([
                            TextInput::make('text')
                                ->label('标题内容'),
                            Select::make('level')
                                ->label('级别')
                                ->options([
                                    'h1' => 'H1',
                                    'h2' => 'H2',
                                ]),
                        ]),
                    Section::make('样式设置')
                        ->schema([
                            Select::make('align')
                                ->label('对齐方式')
                                ->options([
                                    'left' => '左对齐',
                                    'center' => '居中',
                                    'right' => '右对齐',
                                ]),
                            TextInput::make('className')
                                ->label('额外样式类'),
                        ]),
                ]),
        ]);
}
```

### 2. 创建布局和字段组件

#### 布局组件：
- `Grid`：网格布局，支持列数配置
- `Section`：分组区域，带标题
- `Tabs`/`Tab`：标签页布局
- `Columns`/`Column`：多列布局

#### 字段组件：
- `TextInput`：文本输入
- `Textarea`：多行文本
- `Select`：下拉选择
- `Checkbox`：复选框
- `Toggle`：开关
- `NumberInput`：数字输入
- `ColorPicker`：颜色选择
- `DatePicker`：日期选择
- 等等（复用现有 UX Form 组件）

### 3. 改造 ComponentType 基类

```php
abstract class ComponentType
{
    // 保持现有方法不变，为了向后兼容
    
    // 新方法：可以返回 Element, UXComponent, array, 或 SettingsBuilder
    public function settingsSchema(): Element|UXComponent|array|SettingsBuilder
    {
        return []; // 原有的数组格式，向后兼容
    }
    
    // 可选：新的 defaultSettings 从 schema 自动推断
    public function defaultSettings(): array
    {
        // 如果返回 SettingsBuilder，自动提取默认值
    }
}
```

### 4. 改造 PageBuilderPage

修改 `renderInlineSettings` 方法，支持三种格式的渲染：

```php
protected function renderInlineSettings(string $uid, $componentType, array $settings): Element
{
    $schema = $componentType->settingsSchema();
    
    // 情况1：直接返回 Element 或 UXComponent
    if ($schema instanceof Element || $schema instanceof UXComponent) {
        // 包装并添加保存按钮
        $wrapper = Element::make('div')->class('pb-card-settings');
        $wrapper->child($schema);
        $wrapper->child($this->renderActions($uid));
        return $wrapper;
    }
    
    // 情况2：返回 SettingsBuilder
    if ($schema instanceof SettingsBuilder) {
        $form = $schema
            ->bindPrefix($uid)
            ->fill($settings)
            ->render();
        
        $wrapper = Element::make('div')->class('pb-card-settings');
        $wrapper->child($form);
        $wrapper->child($this->renderActions($uid));
        return $wrapper;
    }
    
    // 情况3：原有的数组格式（保持向后兼容）
    return $this->renderLegacySettings($uid, $schema, $settings);
}
```

## 文件结构

```
framework/
  UX/
    Form/
      # 现有组件保持不变
      Input.php
      Select.php
      Textarea.php
      ...
    Settings/
      # 新增的 PageBuilder 特定组件
      SettingsBuilder.php
      Layout/
        Grid.php
        Section.php
        Tabs.php
        Tab.php
        Columns.php
        Column.php
      Fields/
        # 复用现有 Form 组件，添加一些适配
        TextInput.php
        SelectInput.php
        ...
```

## 实现步骤

### 第一步：创建核心接口和基类

1. `SettingsSchemaContract`：所有 schema 组件的接口
2. `SettingsComponent`：所有 schema 组件的基类
3. `SettingsLayoutComponent`：布局组件的基类
4. `SettingsFieldComponent`：字段组件的基类

### 第二步：实现布局组件

1. `Grid` 和 `Columns` 布局
2. `Section` 分组组件
3. `Tabs` 和 `Tab` 标签页组件

### 第三步：实现字段组件（适配现有 UX 组件）

1. 为现有 UX Form 组件创建包装类，适配 SettingsSchema API
2. 或直接改造现有组件，让它们支持 SettingsSchema

### 第四步：实现 SettingsBuilder

1. 主 builder 类，提供链式 API
2. 支持组件注册和嵌套
3. 自动 data-model 绑定

### 第五步：改造 ComponentType 和 PageBuilderPage

1. 更新 `settingsSchema()` 返回类型声明
2. 改造 `renderInlineSettings` 支持三种格式
3. 更新 `defaultSettings()` 支持从 SettingsBuilder 自动推断

### 第六步：更新示例组件

1. 更新 `Heading`、`Paragraph` 等基础组件，展示新 API 用法
2. 保持向后兼容的示例

## 迁移指南

### 从旧数组格式迁移

```php
// 旧格式
public function settingsSchema(): array
{
    return [
        ['key' => 'text', 'type' => 'text', 'label' => '内容'],
        ['key' => 'level', 'type' => 'select', 'label' => '级别', 'options' => [...]],
    ];
}

// 新格式
public function settingsSchema(): SettingsBuilder
{
    return SettingsBuilder::make()
        ->components([
            TextInput::make('text')->label('内容'),
            Select::make('level')->label('级别')->options([...]),
        ]);
}
```

## 优势

1. **灵活性极高**：可以嵌套任何布局和字段组合
2. **代码可读性好**：链式 API 更符合人类阅读习惯
3. **可扩展**：自定义组件很容易
4. **复用现有组件**：可以直接复用 UX Form 组件
5. **向后兼容**：不破坏现有代码
