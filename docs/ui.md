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

## CSRF 保护

框架内置了 CSRF 保护。使用 `form()` 助手函数时，如果 `method` 不是 `GET`，会自动添加 CSRF 隐藏域。

```php
use function Framework\UI\{form, input};

return form(['action' => '/submit', 'method' => 'POST'],
    input(['name' => 'data']),
    input(['type' => 'submit'])
);
```

## 动态组件 (Y-Live)

Y-Live 提供了一种无需编写 JavaScript 即可实现局部页面更新的方案（类似 Livewire）。

```php
use function Framework\UI\LiveComponent;

return LiveComponent('counter', function($props) {
    return div([], 
        "当前计数: " . ($props['count'] ?? 0),
        button(['data-live-action' => 'increment'], "点击增加")
    );
});
```

详情请参阅 [Y-Live 动态更新](live.md)。

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
