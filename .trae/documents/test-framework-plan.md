# 框架测试体系设计方案

## 一、现状分析

### 已有基础设施
- **PHPUnit 10** (`require-dev`)
- **phpunit.xml** — 基础配置，仅含 Unit testsuite，无 coverage、无 bootstrap 完善
- **tests/TestCase.php** — 基础测试类，初始化 Application + Kernel
- **tests/InteractsWithLiveComponents.php** — LiveComponent 测试辅助 trait（`liveCall()`）
- **现有测试**：Application、Cache、Auth、Database、Events、Routing、Lifecycle、Queue、Scheduler、Intl 等

### 缺失的测试覆盖范围
| 模块 | 现状 | 优先级 |
|------|------|--------|
| **UX 组件渲染** (40+ 组件) | ❌ 完全无 | P0 |
| **Element/HTML 构建** | ❌ 完全无 | P0 |
| **AssetRegistry 脚本注册** | ❌ 无 | P0 |
| **DebugBar** | ❌ 无 | P1 |
| **路由系统** | ⚠️ 仅基础 | P1 |
| **LiveComponent 完整生命周期** | ⚠️ 有 trait 但测试少 | P1 |
| **View/Document 文档构建** | ❌ 无 | P1 |

---

## 二、架构设计

### 测试分层策略

```
tests/
├── TestCase.php                          # 基础 TestCase（已有，需增强）
├── InteractsWithLiveComponents.php       # Live 辅助（已有，需增强）
├── InteractsWithUXComponents.php         # 【新增】UX 组件测试辅助
├── InteractsWithElements.php             # 【新增】Element HTML 断言
│
├── Unit/                                 # 单元测试
│   ├── UX/                              # 【新增】UX 组件单元测试
│   │   ├── ButtonTest.php
│   │   ├── ModalTest.php
│   │   ├── AccordionTest.php
│   │   ├── DatePickerTest.php
│   │   ├── DateRangePickerTest.php
│   │   ├── TreeSelectTest.php
│   │   ├── QRCodeTest.php
│   │   ├── PopoverTest.php
│   │   ├── ToastTest.php
│   │   └── ...
│   │
│   ├── View/                             # 【新增】视图层测试
│   │   ├── ElementTest.php               # Element 构建、属性、子元素
│   │   ├── AssetRegistryTest.php          # 脚本/CSS 注册与输出
│   │   └── DocumentTest.php              # 文档构建流程
│   │
│   ├── Routing/                          # （已有，增强）
│   ├── Database/                         # （已有）
│   ├── Events/                           # （已有）
│   └── ...
│
├── Feature/                              # 功能/集成测试
│   ├── UX/
│   │   ├── ComponentRenderTest.php        # 组件完整渲染 + JS 注册集成
│   │   └── LiveBindingTest.php            # liveModel/liveAction 集成
│   ├── Live/
│   │   └── LiveComponentLifecycleTest.php # Live 完整生命周期
│   └── DebugBar/
│       └── DebugBarRenderTest.php
│
└── Support/                              # 【新增】测试工具集
    ├── DOMAssert.php                     # HTML/DOM 断言库
    ├── ComponentSnapshot.php              # 组件快照对比
    └── MockRequest.php                   # HTTP 请求模拟
```

---

## 三、核心组件设计

### 3.1 InteractsWithUXComponents.php — UX 组件测试 Trait

```php
trait InteractsWithUXComponents
{
    // 渲染组件并返回 HTML 字符串
    protected function renderUX(UXComponent $component): string;

    // 渲染组件并返回 DOM (DOMDocument)
    protected function renderUXAsDOM(UXComponent $component): \DOMDocument;

    // 断言组件包含指定 CSS 类
    public function assertComponentHasClass(UXComponent $component, string $class): void;

    // 断言组件包含指定 data 属性
    public function assertComponentHasData(UXComponent $component, string $key, string $value = null): void;

    // 断言组件包含指定子元素
    public function assertComponentContains(UXComponent $component, string $html): void;

    // 断言组件不包含指定内容
    public function assertComponentNotContains(UXComponent $component, string $html): void;
}
```

### 3.2 InteractsWithElements.php — Element HTML 断言 Trait

```php
trait InteractsWithElements
{
    // 从 Element 提取 HTML
    protected function elementHtml(Element $el): string;

    // 解析为 DOMDocument
    protected function elementDOM(Element $el): \DOMDocument;

    // XPath 查询
    protected function elementQuery(Element $el, string $xpath): ?\DOMNodeList;

    // 断言: Element 包含文本
    public function assertElementTextContains(Element $el, string $text): void;

    // 断言: Element 有属性
    public function assertElementHasAttribute(Element $el, string $attr, string $value = null): void;

    // 断言: Element 有 CSS 类
    public function assertElementHasClass(Element $el, string $class): void;

    // 断言: Element 子节点数量
    public function assertElementChildCount(Element $el, int $expected): void;

    // 断言: Element 的 data-* 属性集合
    public function assertElementDataAttributes(Element $el, array $attrs): void;
}
```

### 3.3 DOMAssert.php — 通用 DOM 断言库

```php
class DOMAssert
{
    // HTML 字符串断言
    public static function assertSelectorExists(string $html, string $selector): void;
    public static function assertSelectorCount(string $html, string $selector, int $count): void;
    public static function assertContainsText(string $html, string $text): void;
    public static function assertAttributeEquals(string $html, string $selector, string $attr, string $value): void;

    // DOMDocument 断言
    public static function assertXPathCount(\DOMDocument $dom, string $xpath, int $count): void;
    public static function assertXPathContains(\DOMDocument $dom, string $xpath, string $text): void;
}
```

### 3.4 增强 TestCase.php

在现有基础上增加：
- UX 组件测试环境初始化（模拟 `config()` / `cache()` / `asset()` 等助手函数）
- Element 测试环境（确保 View\Base\Element 可正常工作）

---

## 四、实施计划

### Phase 1：基础设施（P0）
| 步骤 | 内容 | 文件 |
|------|------|------|
| 1.1 | 创建 `Support/DOMAssert.php` — 通用 DOM 断言库 | 新建 |
| 1.2 | 创建 `Support/ComponentSnapshot.php` — 组件快照对比 | 新建 |
| 1.3 | 创建 `InteractsWithElements.php` — Element 断言 trait | 新建 |
| 1.4 | 创建 `InteractsWithUXComponents.php` — UX 组件断言 trait | 新建 |
| 1.5 | 增强 `TestCase.php` — 补充 UX/View 测试所需 mock | 修改 |
| 1.6 | 增强 `phpunit.xml` — 增加 coverage、stop-on-failure、verbose | 修改 |

### Phase 2：UX 核心组件测试（P0）
| 步骤 | 组件 | 测试要点 |
|------|------|----------|
| 2.1 | UXComponent 基类 | init() 注册、registerJs/registerCss 调用、render() 输出 |
| 2.2 | Button | label/color/size/type 属性 → 正确 HTML |
| 2.3 | Modal | title/content/open → 结构正确、data 属性 |
| 2.4 | Accordion | items/multiple/dark → item 结构、header/collapse |
| 2.5 | DatePicker | value/format/minDate/maxDate → data 属性 |
| 2.6 | DateRangePicker | startValue/endValue/showTime → dropdown 结构 |
| 2.7 | TreeSelect | treeData/value/multiple/search → node 结构、data-node-value |
| 2.8 | QRCode | value/size/color/bgColor → canvas 元素、data 属性 |
| 2.9 | Popover | placement/trigger/title/content → wrapper 结构 |
| 2.10 | Toast | type/message/duration → 结构和 class |
| 2.11 | Tabs | items/active → tab/panel 结构 |
| 2.12 | Transfer | dataSource/targetKeys → 列表结构 |

### Phase 3：View 层测试（P0）
| 步骤 | 内容 |
|------|------|
| 3.1 | ElementTest — make/id/class/attr/style/data/text/html/child/children |
| 3.2 | ElementTest — liveModel/liveAction/liveFragment/liveParams/bindOn |
| 3.3 | AssetRegistryTest — registerScript/registerScript/inlineStyle/ui()/ux() |
| 3.4 | DocumentTest — 构建文档、注入资源、输出 HTML |

### Phase 4：Feature/集成测试（P1）
| 步骤 | 内容 |
|------|------|
| 4.1 | UX 组件 JS 注册集成测试 — registerJs 是否产出正确 script 标签 |
| 4.2 | LiveComponent + UX 绑定测试 — liveModel 同步值到隐藏 input |
| 4.3 | DebugBar 渲染测试 — Accordion/Tabs 在 DebugBar 中正常工作 |
| 4.4 | 路由功能增强测试 |

---

## 五、测试示例（以 TreeSelect 为例）

```php
<?php

namespace Tests\Unit\UX\Form;

use Tests\TestCase;
use Tests\InteractsWithUXComponents;
use Framework\UX\Form\TreeSelect;

class TreeSelectTest extends TestCase
{
    use InteractsWithUXComponents;

    public function test_it_renders_basic_structure(): void
    {
        $select = TreeSelect::make()
            ->treeData([
                ['label' => '技术部', 'children' => [
                    ['label' => '前端组'],
                    ['label' => '后端组'],
                ]],
            ])
            ->placeholder('选择部门');

        $this->assertComponentHasClass($select, 'ux-tree-select');
        $this->assertComponentHasData($select, 'tree-placeholder', '选择部门');
        $this->assertComponentContains($select, 'ux-tree-select-selector');
        $this->assertComponentContains($select, 'ux-tree-select-dropdown');
        $this->assertComponentContains($select, 'ux-tree-select-tree');
    }

    public function test_it_falls_back_to_label_as_value(): void
    {
        $select = TreeSelect::make()
            ->treeData([
                ['label' => '技术部'],  // 没有 value 字段
            ]);

        // 应自动用 label 作为 data-node-value
        $this->assertComponentContains($select, 'data-node-value="技术部"');
    }

    public function test_multiple_mode_renders_correct_classes(): void
    {
        $select = TreeSelect::make()
            ->treeData([['label' => 'A']])
            ->multiple();

        $this->assertComponentHasClass($select, 'ux-tree-select-multiple');
    }

    public function test_search_mode_renders_input_with_value(): void
    {
        $select = TreeSelect::make()
            ->treeData([['label' => '技术部', 'value' => 'tech']])
            ->showSearch()
            ->value('tech');

        $html = $this->renderUX($select);
        $this->assertStringContainsString($html, 'value="技术部"');
    }

    public function test_disabled_state(): void
    {
        $select = TreeSelect::make()
            ->treeData([['label' => 'A']])
            ->disabled();

        $this->assertComponentHasClass($select, 'ux-tree-select-disabled');
    }
}
```

---

## 六、运行方式

```bash
# 运行全部测试
./vendor/bin/phpunit

# 只跑 UX 组件测试
./vendor/bin/phpunit --testsuite=Unit --filter="UX"

# 带覆盖率报告
./vendor/bin/phpunit --coverage-html=coverage

# 只跑某个组件
./vendor/bin/phpunit tests/Unit/UX/Form/TreeSelectTest.php
```

---

## 七、依赖确认

| 依赖 | 用途 | 状态 |
|------|------|------|
| phpunit/phpunit ^10.0 | 测试框架 | ✅ 已有 |
| dom extension (PHP 内置) | DOMDocument 解析 HTML | ✅ PHP 8.4 内置 |
| xml extension (PHP 内置) | XPath 查询 | ✅ PHP 8.4 内置 |
| mbstring (PHP 内置) | 字符串处理 | ✅ PHP 8.4 内置 |

无需额外安装依赖。
