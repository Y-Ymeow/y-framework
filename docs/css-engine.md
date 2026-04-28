# CSS Engine

动态CSS生成引擎，根据项目使用的类名生成精简的CSS文件。

## 工作原理

1. 扫描PHP源文件中的 `->class('...')` 和 `class="..."` 字符串
2. 解析每个类名的修饰符（响应式前缀、伪类）
3. 从各个Rules类查找匹配的CSS规则
4. 生成单个CSS文件

## 命令行使用

```bash
php bin/console css:generate
php bin/console css:generate --output=public/assets/css/custom.css
php bin/console css:generate --minify
```

## 类名解析

### 响应式前缀

| 前缀 | 媒体查询 |
|------|----------|
| `sm:` | `(min-width: 640px)` |
| `md:` | `(min-width: 768px)` |
| `lg:` | `(min-width: 1024px)` |
| `xl:` | `(min-width: 1280px)` |
| `2xl:` | `(min-width: 1536px)` |
| `max-sm:` | `(max-width: 639px)` |
| `max-md:` | `(max-width: 767px)` |
| `max-lg:` | `(max-width: 1023px)` |
| `max-xl:` | `(max-width: 1279px)` |

### 伪类

| 前缀 | CSS选择器 |
|------|-----------|
| `hover:` | `:hover` |
| `focus:` | `:focus` |
| `active:` | `:active` |
| `visited:` | `:visited` |
| `first-child:` | `:first-child` |
| `last-child:` | `:last-child` |
| `disabled:` | `:disabled` |
| `enabled:` | `:enabled` |
| `checked:` | `:checked` |
| `after:` | `::after` |
| `before:` | `::before` |
| `selection:` | `::selection` |

### 组合示例

```php
// 在PHP中使用
->class('md:hover:bg-blue-700')
->class('sm:focus:w-1')
->class('lg:active:opacity-50')
```

### 解析结果

| 类名 | base | media | pseudo |
|------|------|-------|--------|
| `hover:bg-blue-700` | `bg-blue-700` | - | `:hover` |
| `md:hover:bg-blue-700` | `bg-blue-700` | `(min-width: 768px)` | `:hover` |
| `sm:focus:w-1` | `w-1` | `(min-width: 640px)` | `:focus` |
| `block` | `block` | - | - |

### 生成的CSS

```css
/* 基础类 */
.bg-blue-700{background-color:#1d4ed8}

/* 带伪类的类 */
.hover\:bg-blue-700:hover{background-color:#1d4ed8}

/* 带媒体查询的类 */
@media (min-width: 768px) {
  .md\:hover\:bg-blue-700:hover{background-color:#1d4ed8}
}
```

## Rules 类

每个Rules类负责解析特定的CSS属性：

| 类 | 职责 |
|----|------|
| `LayoutRules` | display, flex, grid, position, width, height |
| `SpacingRules` | margin, padding |
| `TypographyRules` | font-size, font-weight, text-align, line-height |
| `ColorRules` | color, background-color, border-color |
| `BorderRules` | border-width, border-radius, border-style |
| `EffectRules` | box-shadow, opacity |
| `InteractionRules` | cursor, outline, user-select |
| `TransitionRules` | transition-property, transition-duration |
| `TransformRules` | transform, rotate, scale |
| `AnimationRules` | animation, @keyframes |

## 动态值支持

支持数值型类的动态解析：

```php
// w-1 到 w-96 (4px 步进)
->class('w-18')  // width: 4.5rem

// h-1 到 h-96
->class('h-8')    // height: 2rem

// p-1 到 p-96 (4px 步进)
->class('p-4')    // padding: 1rem

// m-1 到 m-96
->class('m-2')    // margin: 0.5rem

// top-, bottom-, left-, right-
->class('top-4')  // top: 1rem

// z-0 到 z-50
->class('z-10')   // z-index: 10
```

## 动画

```php
->class('animate-spin')
->class('animate-pulse')
->class('animate-bounce')
->class('animate-fade-in')
->class('animate-fade-out')
->class('animate-slide-up')
->class('animate-slide-down')
->class('animate-scale-in')
->class('animate-ping')
```

生成的CSS包含@keyframes定义：

```css
@keyframes spin{to{transform:rotate(360deg)}}
.animate-spin{animation:spin 1s linear infinite}
```

## 文件结构

```
src/CSS/
├── CSSEngine.php        # 核心解析器
├── CSSReset.php         # Tailwind CSS Reset
├── LayoutRules.php      # 布局规则
├── SpacingRules.php     # 间距规则
├── TypographyRules.php  # 排版规则
├── ColorRules.php       # 颜色规则
├── BorderRules.php      # 边框规则
├── EffectRules.php      # 效果规则
├── InteractionRules.php # 交互规则
├── TransitionRules.php  # 过渡规则
├── TransformRules.php   # 变换规则
└── AnimationRules.php   # 动画规则
```

## 添加新的Rules

创建新的Rules类并实现 `parse(string $class): ?string` 方法：

```php
<?php

declare(strict_types=1);

namespace Framework\CSS;

class CustomRules
{
    public static function parse(string $class): ?string
    {
        if ($class === 'custom-style') {
            return 'custom-property:value';
        }
        // 返回null表示不匹配
        return null;
    }
}
```

然后在 `CssGenerateCommand.php` 的 `$parsers` 数组中添加：

```php
$parsers = [
    // ... 其他parsers
    CustomRules::class,
];
```

## 扩展CSSEngine

### 添加新的响应式断点

修改 `CSSEngine::$breakpoints`：

```php
private static array $breakpoints = [
    // ... 现有
    '3xl' => '(min-width: 1920px)',
];
```

### 添加新的伪类

修改 `CSSEngine::$pseudoClasses`：

```php
private static array $pseudoClasses = [
    // ... 现有
    'placeholder-shown' => ':placeholder-shown',
];
```
