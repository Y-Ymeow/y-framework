# 现代化前端集成 (Vite & Inertia)

本框架支持通过 Vite 进行资源打包，并提供了类似 Inertia.js 的 SPA (单页应用) 开发体验。

## Vite 资源管理

框架内置了 `vite()` 助手函数，可根据环境自动切换开发模式（HMR）或生产模式（Manifest 解析）。

### 配置

1. 确保根目录有 `vite.config.js`。
2. 在 PHP 中加载资源：

```php
use function Framework\Support\vite;

echo vite('resources/js/app.js');
```

### 开发环境

运行 Vite 开发服务器：
```bash
npm run dev
```
此时 `vite()` 将指向 `http://localhost:5173` 并支持热重载。

### 生产环境

构建资源：
```bash
npm run build
```
此时 `vite()` 将读取 `public/build/.vite/manifest.json` 并生成带有哈希值的资源链接。

## Inertia SPA 渲染

Inertia 允许你使用经典的服务器端路由和控制器，同时享受现代 SPA（如 React, Vue, Svelte）的前端体验。

### 使用 `inertia()` 助手

```php
use function Framework\Support\inertia;

#[Route(path: '/profile', methods: ['GET'])]
function UserProfile() {
    return inertia('UserProfile', [
        'user' => [
            'name' => 'ymeow',
            'email' => 'ymeow@example.com'
        ]
    ]);
}
```

### 工作原理

- **首次加载**：返回一个包含 `data-page` 属性的 HTML 壳组件。
- **后续跳转**：当前端发送带有 `X-Inertia` 请求头的 AJAX 请求时，后端仅返回包含 `component` 和 `props` 的 JSON 数据。

## 混合模式

你可以根据需要自由组合：
- 对于内容密集的页面，使用 **Functional UI** + **Y-Live** 获得最佳的 SEO 和响应速度。
- 对于交互极其复杂的模块（如富文本编辑器、实时面板），使用 **Inertia** 集成现代 JS 框架。
