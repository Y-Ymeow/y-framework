# DocGen v2.0 — 通用 PHP 文档生成引擎

> 从源码注释自动生成多类型文档，支持全项目所有模块。

## 快速开始

```bash
php docgen --init    # 初始化配置文件（首次运行）
php docgen           # 生成所有已配置模块的文档
php docgen ux        # 只生成 UX 模块
php docgen --all     # 全量生成（含无注释的类）
php docgen --type api # 只生成 API 文档
```

## 输出示例

| 命令 | 输出文件 |
|---|---|
| `php docgen` | `docs/ux-api.md`, `docs/ux-dev.md`, `docs/live-api.md` ... |
| `php docgen ux` | 只处理 UX 模块 |
| `php docgen --type arch` | 所有模块的架构文档 |

---

## 配置文件

配置文件：`docgen.config.json`

```json
{
    "version": "2.0",
    "output_dir": "docs",
    "modules": {
        "ux": {
            "title": "UX 组件库",
            "description": "前端交互式组件库...",
            "scan": "src/UX",
            "namespace": "Framework\\UX",
            "tags": {
                "category": "ux-category",
                "since": "ux-since",
                "example": "ux-example",
                "internal": "ux-internal"
            },
            "templates": ["api", "dev"]
        },
        "live": { ... }
    }
}
```

### 配置项说明

| 字段 | 说明 | 必填 |
|---|---|---|
| `title` | 模块显示标题 | ✅ |
| `description` | 模块描述（用于架构文档） | ❌ |
| `scan` | 扫描目录路径 | ✅ |
| `namespace` | 命名空间前缀 | ✅ |
| `tags` | 标签映射表 | ✅ |
| `templates` | 生成的文档类型 | ❌ 默认 ['api', 'dev'] |

### templates 可选值

| 值 | 输出文件 | 内容 |
|---|---|---|
| `api` | `{module}-api.md` | API 参考（使用者视角） |
| `dev` | `{module}-dev.md` | 开发文档（开发者视角） |
| `arch` | `{module}.md` | 架构文档（目录树 + 描述） |
| `index` | `{module}-index.md` | 文件索引（代码导航） |

---

## 注释规范

### 类注释

```php
/**
 * 组件/类 中文名
 *
 * 功能描述。第二行补充说明。
 *
 * @module-category 分类名      ← 对应 config tags.category
 * @module-since 版本号          ← 对应 config tags.since
 * @module-example 最简用法       ← 对应 config tags.example（可多个）
 */
class Foo extends Bar {
}
```

**注意**：标签前缀由 `docgen.config.json` 的 `tags` 配置决定。
- UX 模块用 `@ux-*`
- Live 模块用 `@live-*`
- View 模块用 `@view-*`
- Routing 用 `@route-*`
- HTTP 用 `@http-*`
- Admin 用 `@admin-*`

### 方法注释

```php
/**
 * 方法简述
 * @param 类型 $参数 参数说明
 * @return static
 * @module-example 组件::make()->方法(参数)
 * @module-default true          ← 仅 bool 参数需要
 */

public function foo(string $bar): static { }
```

### 内部方法标记

不需要出现在 API 文档中的方法加：

```php
/**
 * @internal
 */
protected function toElement(): Element { }
```

### 标签速查表

| 标签 | 作用 | 出现在 |
|---|---|---|
| `@{prefix}-category` | 分类分组 | 类注释 |
| `@{prefix}-since` | 引入版本 | 类注释 |
| `@{prefix}-example` | 用法示例 | 类/方法注释 |
| `@{prefix}-internal` | 标记内部方法 | 方法注释 |
| `@{prefix}-default` | bool 参数默认值 | 方法注释 |
| `@param` | 标准参数 | 方法注释 |
| `@return` / `@var` | 返回值 | 方法注释 |
| `@deprecated` | 已废弃 | 方法注释 |

---

## 各模块标签前缀

| 模块 | category | since | example | internal |
|---|---|---|---|---|
| **UX** | `ux-category` | `ux-since` | `ux-example` | `ux-internal` |
| **Live** | `live-category` | `live-since` | `live-example` | `live-internal` |
| **View** | `view-category` | `view-since` | `view-example` | `view-internal` |
| **Routing** | `route-category` | `route-since` | `route-example` | `route-internal` |
| **HTTP** | `http-category` | `http-since` | `http-example` | `http-internal` |
| **Admin** | `admin-category` | `admin-since` | `admin-example` | `admin-internal` |
| **Database** | `db-category` | `db-since` | `db-example` | `db-internal` |
| **Console** | `console-category` | `console-since` | `console-example` | `console-internal` |

---

## 小模型任务模板

给模块批量加注释时使用此模板：

```
任务：为 [模块名] 的核心类添加 DocGen 注释

目标目录：src/[Module]/
规范参考：docs/docgen-spec.md

要求：
1. 给每个 public 类添加类注释（含 @module-category, @module-since, @module-example）
2. 给每个 public 方法添加方法注释（含简述、@param、@return、@module-example）
3. bool 参数加 @module-default
4. toElement()/buildElement() 等内部方法加 @internal
5. 不修改任何代码逻辑，只加注释
6. 完成后运行 php docgen 验证输出
```

---

## 与 v1.0 的区别

| 特性 | v1.0 (旧) | v2.0 (当前) |
|---|---|---|
| 扫描范围 | 仅 src/UX | 全项目任意模块 |
| 标签体系 | 硬编码 @ux-* | 配置驱动的 @{prefix}-* |
| 输出格式 | 固定两份 | 可选 api/dev/arch/index |
| 配置方式 | 无 | docgen.config.json |
| 初始化 | 无 | `--init` 自动生成 |
| 过滤器 | 无 | category 分组 + internal 隐藏 |
| 支持内容 | 类+方法 | 类+方法+常量+属性+继承关系 |
