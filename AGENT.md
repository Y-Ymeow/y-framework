# 框架开发指南 (AGENT.md)

本文件定义了 PHP 框架的核心开发模式与工程标准，开发时必须严格遵守。

---

## 1. 目录结构

```
app/                  # 应用层代码
admin/                # 管理后台模块
framework/            # 核心框架代码
resources/js/         # 前端源资源 (Vite)
public/build/         # Vite 构建输出
database/             # 数据库迁移
config/               # 配置文件
```

---

## 2. View 系统 (Element)

- **一切皆元素**: 优先使用 `Framework\View\Element` 及其子类（`Container`, `Text` 等）构建 UI，避免原始 HTML 字符串拼接。
- **Element 核心能力**: `make()`, `id()`, `class()`, `attr()`, `text()`, `html()`, `child()`, `cloak()`
- **Tailwind 支持**: 使用 trait `HasTailwindAppearance`, `HasTailwindLayout`, `HasTailwindSpacing`, `HasTailwindTypography`
- **自动包装**: `Response::html()` 自动检测内容，不含 `<html>` 时用 `Document` 包装。
- **静态配置**: `Document::setTitle()`, `Document::uxStatic()` 等静态方法在路由层配置页面元数据。

---

## 3. Live 系统 (y-live)

- **状态同步**: `LiveComponent` 的 `public` 属性自动与前端 `state` 同步。
- **生命周期**: `boot()` → `mount()` → `hydrate()` → actions → `dehydrate()`。`mount()` 在构造函数末尾自动调用。
- **Action 属性**: 使用 `#[LiveAction]` 定义动作方法。
- **返回响应**: `LiveResponse::make()` 支持 `->fragment()`, `->toast()`, `->dispatch()` 链式调用。
- **内置指令**: 优先使用 `$this->toast()`, `$this->openModal()` 等方法与前端交互。
- **通用集成**: `$this->ux($component, $id, $action, $data)` 发送精准组件指令。
- **分片更新**: 在 `render()` 中对需要局部更新的元素调用 `->liveFragment('name')`，然后 `LiveResponse::make()->fragment('name', $html)` 返回更新。

---

## 4. UX 组件 (y-ux)

- **位置**: `framework/UX/` 下按功能分类（Form, Data, Dialog, Navigation 等）。
- **基类**: `UXComponent` / `UXLiveComponent`
- **Live 绑定**: 组件支持 `liveAction()` 或 `liveModel()` 实现与服务器端联动。
- **样式前缀**: 所有 UX 样式以 `.ux-` 为前缀。
- **Toast**: 后端 `LiveResponse::toast()` 或 `$this->toast()` 发送操作，前端 UX Toast 组件自动拦截并显示。

---

## 5. 资源管理 (Vite + Assets)

- **前端入口**: `resources/js/ui.js`, `resources/js/ux.js`
- **Vite 构建**: 修改 `resources/js/` 下的 JS/CSS 后，执行 `cd resources/js && npm run build` 或 `bun run build`
- **输出目录**: `public/build/`
- **核心资源**: `Document` 渲染时自动加载 `AssetRegistry::core()`，包括 CSS 引擎和基础 y-ui 库。
- **Asset 类**: `Framework\Support\Asset::vite()`, `Asset::isDev()`

---

## 6. 路由系统

- **属性路由**: `#[Route('/path', methods: ['GET'])]`，`methods` 支持 `string|array`
- **路由组**: `#[RouteGroup('/prefix')]` 定义前缀
- **中间件**: `#[Middleware(['auth'])]` 绑定中间件
- **缓存**: `php bin/console route:cache`，`php bin/console cache:clear route`

---

## 7. 数据库 / ORM

- **Model 基类**: `Framework\Database\Model`，主键、表名、可批量赋值字段、类型转换
- **关系**: `hasMany`, `belongsTo`, `belongsToMany`, `hasOne`, `morphMany`, `morphTo`
- **Traits**: `HasTranslations` (多语言), `HasSoftDeletes` (软删除), `HasAuth`
- **迁移**: `php bin/console migrate` 执行迁移
- **种子**: `php bin/console db:seed` 执行种子
- **Schema**: `Framework\Database\Schema\Blueprint` 定义表结构

---

## 8. 组件属性系统

- `#[Prop]` - 属性
- `#[State]` - 状态
- `#[Persistent]` - 持久化
- `#[Computed]` - 计算属性
- `#[Locked]` - 锁定

---

## 9. 前端架构 (y-directive)

- **指令引擎**: `resources/js/y-directive/` 是响应式指令引擎，类似 Vue 的声明式绑定
- **事件委托**: `ux.js` 必须使用全局事件委托，确保 DOM Patch 后交互逻辑依然有效
- **操作管道**: `y-live` 的 `dispatchAction` 必须使用 `L.executeOperation()` 而非直接导入的函数

---

## 10. 注意事项

- **序列化**: `LiveComponent` 的 `operations` 属性已排除在序列化外，确保指令不累加
- **构建**: 修改 `resources/js/` 后必须重新构建才能生效
- **语言包**: `resources/lang/` 目录存放多语言文件，en/ 和 zh/ 两套