# Functional UI (函数式 UI)

本框架彻底抛弃了 `.blade.php` 或 `.twig` 这种混合 HTML 的模板，转而使用纯 PHP 函数来构建 UI。

## 基本用法

使用 `functions/ui.php` 中提供的标签函数：

```php
use function Framework\UI\{div, h1, p};

function WelcomeCard($name) {
    return div(['class' => 'card'],
        h1([], "你好, $name"),
        p([], "欢迎使用我们的高性能框架。")
    );
}
```

## 整体文档

使用 `Document` 壳组件生成完整的 HTML：

```php
return Document("首页", [], [
    WelcomeCard("ymeow")
]);
```

## 片段缓存 (Fragment Caching)

对于昂贵的组件，可以使用 `cache_ui`：

```php
return cache_ui('sidebar_data', fn() => 
    div(['class' => 'sidebar'], 
        // 复杂的侧边栏生成逻辑
    )
);
```

## 优势

1. **类型安全**：可以使用 PHP 的参数类型约束。
2. **零 I/O**：不需要读取模板文件。
3. **OpCache 友好**：UI 逻辑直接编译为字节码。
